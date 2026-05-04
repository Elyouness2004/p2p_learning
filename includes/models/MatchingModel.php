<?php
// includes/models/MatchingModel.php
// Encapsulates the full matching algorithm.
// No HTML, no session access — receives userId and returns scored matches.

class MatchingModel
{
    public static function getMatches(mysqli $conn, int $userId): array
    {
        // ── 1. Current user's modules ─────────────────────────────
        $stmt = $conn->prepare(
            'SELECT module_id, type FROM user_modules WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $myMaitrise = [];
        $myLacunes  = [];
        foreach ($rows as $row) {
            if ($row['type'] === 'maitrise') $myMaitrise[] = $row['module_id'];
            else                             $myLacunes[]  = $row['module_id'];
        }

        // ── 2. All other users with their modules ─────────────────
        $stmt = $conn->prepare('
            SELECT u.id, u.name, u.email, u.school, u.field, u.avatar, u.first_name,
                   um.module_id, um.type, m.name AS module_name
            FROM users u
            JOIN user_modules um ON um.user_id = u.id
            JOIN modules m       ON m.id = um.module_id
            WHERE u.id != ?
            ORDER BY u.id
        ');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $allRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // ── 3. Group by user ──────────────────────────────────────
        $candidates = [];
        foreach ($allRows as $row) {
            $uid = $row['id'];
            if (!isset($candidates[$uid])) {
                $candidates[$uid] = [
                    'id'         => $uid,
                    'name'       => $row['name'],
                    'email'      => $row['email'],
                    'school'     => $row['school'],
                    'field'      => $row['field'],
                    'avatar'     => $row['avatar'],
                    'first_name' => $row['first_name'],
                    'maitrise'   => [],
                    'lacune'     => [],
                ];
            }
            $candidates[$uid][$row['type']][$row['module_id']] = $row['module_name'];
        }

        // ── 4. Batch-fetch ratings for all candidates ─────────────
        $ratingsMap = [];
        if (!empty($candidates)) {
            $ids         = implode(',', array_map('intval', array_keys($candidates)));
            $ratingsRows = $conn->query(
                "SELECT rated_id,
                        ROUND(AVG((score_clarity + score_punctuality + score_engagement) / 3), 1) AS avg,
                        COUNT(*) AS total
                 FROM ratings WHERE rated_id IN ($ids)
                 GROUP BY rated_id"
            )->fetch_all(MYSQLI_ASSOC);
            foreach ($ratingsRows as $r) {
                $ratingsMap[$r['rated_id']] = $r;
            }
        }

        // ── 5. Score compatibility ────────────────────────────────
        $matches = [];
        foreach ($candidates as $c) {
            $teachThemIds = array_intersect($myMaitrise, array_keys($c['lacune']));
            $learnFromIds = array_intersect(array_keys($c['maitrise']), $myLacunes);

            $score = count($teachThemIds) + count($learnFromIds);
            if ($score === 0) continue;

            $type = (count($teachThemIds) > 0 && count($learnFromIds) > 0)
                  ? 'parfait'
                  : 'partiel';

            $matches[] = [
                'id'              => $c['id'],
                'name'            => $c['name'],
                'email'           => $c['email'],
                'school'          => $c['school'],
                'field'           => $c['field'],
                'avatar'          => $c['avatar'],
                'first_name'      => $c['first_name'],
                'score'           => $score,
                'type'            => $type,
                'je_lui_apprends' => array_intersect_key($c['lacune'],   array_flip($teachThemIds)),
                'il_mapprend'     => array_intersect_key($c['maitrise'], array_flip($learnFromIds)),
                'avg_rating'      => isset($ratingsMap[$c['id']]) ? (float)$ratingsMap[$c['id']]['avg'] : null,
                'nb_ratings'      => isset($ratingsMap[$c['id']]) ? (int)$ratingsMap[$c['id']]['total'] : 0,
            ];
        }

        // ── 6. Sort: parfait → score modules desc → réputation desc ──
        usort($matches, function ($a, $b) {
            if ($a['type'] !== $b['type']) {
                return $a['type'] === 'parfait' ? -1 : 1;
            }
            if ($a['score'] !== $b['score']) {
                return $b['score'] - $a['score'];
            }
            return ($b['avg_rating'] ?? 0) <=> ($a['avg_rating'] ?? 0);
        });

        return $matches;
    }

    public static function getUserModuleIds(mysqli $conn, int $userId): array
    {
        $stmt = $conn->prepare(
            'SELECT module_id, type FROM user_modules WHERE user_id = ?'
        );
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        $result = ['maitrise' => [], 'lacune' => []];
        foreach ($rows as $row) {
            $result[$row['type']][] = $row['module_id'];
        }
        return $result;
    }
}
