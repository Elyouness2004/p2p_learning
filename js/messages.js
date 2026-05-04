/**
 * js/messages.js
 * AJAX messaging: send without reload, poll for new messages every 3 s.
 */

document.addEventListener('DOMContentLoaded', () => {
  const zone    = document.getElementById('messages-zone');
  const form    = document.getElementById('compose-form');
  const textarea = form?.querySelector('textarea');
  const counter  = document.getElementById('msg-counter');

  if (!zone) return;

  const partnerId = zone.dataset.partnerId;
  const baseUrl   = zone.dataset.baseUrl;
  const myId      = zone.dataset.me;

  // Scroll to bottom on load
  zone.scrollTop = zone.scrollHeight;

  if (!partnerId || partnerId === '0' || !form) return;

  let lastId = parseInt(zone.dataset.lastId || '0', 10);

  // ── Char counter ─────────────────────────────────────────────────
  function updateCounter() {
    if (!counter || !textarea) return;
    const len = (textarea.value || '').length;
    counter.textContent = len + ' / 2000';
    counter.classList.toggle('text-danger', len > 1900);
  }

  // ── Textarea: auto-grow, Enter to send ───────────────────────────
  if (textarea) {
    textarea.addEventListener('input', () => {
      textarea.style.height = 'auto';
      textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
      updateCounter();
    });

    textarea.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }

  // ── AJAX send ─────────────────────────────────────────────────────
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    sendMessage();
  });

  async function sendMessage() {
    if (!textarea) return;
    const content = textarea.value.trim();
    if (!content || content.length > 2000) return;

    const csrfInput = form.querySelector('[name="csrf_token"]');
    const fd = new FormData();
    fd.append('csrf_token', csrfInput?.value ?? '');
    fd.append('to', partnerId);
    fd.append('content', content);

    textarea.value = '';
    textarea.style.height = 'auto';
    updateCounter();

    try {
      const resp = await fetch(baseUrl + '/pages/messages/send.php', {
        method: 'POST',
        body: fd,
      });
      const data = await resp.json();
      if (data.ok && data.message) {
        appendMessage(data.message, true);
        lastId = data.message.id;
      }
    } catch (_) {}
  }

  // ── AJAX polling every 3 s ────────────────────────────────────────
  setInterval(() => {
    fetch(`${baseUrl}/pages/messages/poll.php?with=${partnerId}&after=${lastId}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.messages?.length) {
          data.messages.forEach((msg) => {
            appendMessage(msg, String(msg.sender_id) === myId);
          });
          lastId = data.messages.at(-1).id;
        }
      })
      .catch(() => {});
  }, 3000);

  // ── Append a message bubble ───────────────────────────────────────
  function appendMessage(msg, isMine) {
    const time = msg.sent_at
      ? msg.sent_at.substring(11, 16)
      : new Date().toTimeString().substring(0, 5);

    const row = document.createElement('div');
    row.className = 'bubble-row' + (isMine ? ' mine' : '');

    if (!isMine) {
      const av = document.createElement('div');
      av.className = 'conv-avatar';
      av.style.cssText = 'width:26px;height:26px;font-size:.7rem;flex-shrink:0;';
      av.textContent = (msg.sender_name || '?').charAt(0).toUpperCase();
      row.appendChild(av);
    }

    const inner = document.createElement('div');
    inner.className = 'bubble-content';  // ← aligns time+bubble as a column

    const timeDiv = document.createElement('div');
    timeDiv.className = 'bubble-time';
    timeDiv.textContent = time;

    const bubble = document.createElement('div');
    bubble.className = 'bubble ' + (isMine ? 'mine' : 'theirs');
    bubble.innerHTML = escHtml(msg.content).replace(/\n/g, '<br>');

    inner.appendChild(timeDiv);
    inner.appendChild(bubble);
    row.appendChild(inner);

    zone.appendChild(row);
    zone.scrollTop = zone.scrollHeight;
  }

  function escHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
  }
});
