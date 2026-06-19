let conversations = [];
let activeConvId = null;
let filterType = 'all';

async function loadConversations() {
  const data = await apiFetch('messages.php?action=list');
  conversations = data.conversations;
  renderConvList();
}

function getFilteredConversations() {
  const search = (document.getElementById('convSearch')?.value || '').toLowerCase();
  let filtered = conversations;

  if (filterType === 'teammate') {
    filtered = filtered.filter(c => c.conv_type === 'teammate');
  } else if (filterType === 'projects') {
    filtered = filtered.filter(c => c.gig_title);
  }

  if (search) {
    filtered = filtered.filter(c =>
      c.partner_name.toLowerCase().includes(search) ||
      (c.gig_title || '').toLowerCase().includes(search)
    );
  }
  return filtered;
}

function renderConvList() {
  const list = document.getElementById('convList');
  const filtered = getFilteredConversations();

  if (filtered.length === 0) {
    list.innerHTML = '<div class="empty-state" style="padding:2rem"><p>No conversations in this tab yet.</p></div>';
    return;
  }

  list.innerHTML = filtered.map(c => `
    <div class="conv-item${activeConvId === c.conversation_id ? ' active' : ''}" onclick="selectConv(${c.conversation_id})">
      <div class="avatar" style="background:${c.partner_color}">${escapeHtml(c.partner_initials)}</div>
      <div class="conv-info">
        <div class="conv-name">${escapeHtml(c.partner_name)}</div>
        <div class="conv-project">${escapeHtml(c.gig_title || 'Project chat')}</div>
        <div class="conv-preview">${escapeHtml(c.last_message || 'No messages yet')}</div>
        <span class="conv-badge ${c.conv_type === 'host' ? 'badge-host' : 'badge-team'}">${c.conv_type === 'host' ? 'Host' : 'Teammate'}</span>
      </div>
    </div>
  `).join('');
}

async function selectConv(id) {
  activeConvId = id;
  renderConvList();
  const data = await apiFetch('messages.php?action=messages&conversation_id=' + id);
  const info = data.info;

  document.getElementById('chatName').textContent = info.partner_name;
  document.getElementById('chatGig').textContent = info.gig_title || 'Project';
  document.getElementById('chatBadge').textContent = info.conv_type === 'host' ? 'Host' : 'Teammate';
  document.getElementById('chatBadge').className = 'conv-badge ' + (info.conv_type === 'host' ? 'badge-host' : 'badge-team');

  const area = document.getElementById('msgsArea');
  area.innerHTML = data.messages.map(m => {
    const sent = parseInt(m.sender_id) === parseInt(currentUser.id);
    return `<div class="msg-row${sent ? ' sent' : ''}"><div class="bubble ${sent ? 'bubble-sent' : 'bubble-recv'}">${escapeHtml(m.body)}</div></div>`;
  }).join('');
  area.scrollTop = area.scrollHeight;

  document.getElementById('chatPanel').style.display = 'flex';
  document.getElementById('emptyChat').style.display = 'none';
}

async function sendMessage() {
  const input = document.getElementById('msgInput');
  const body = input.value.trim();
  if (!body || !activeConvId) return;
  try {
    await apiFetch('messages.php?action=send', {
      method: 'POST',
      body: { conversation_id: activeConvId, body },
    });
    input.value = '';
    await selectConv(activeConvId);
    await loadConversations();
  } catch (e) {
    showToast(e.message);
  }
}

function setFilter(type) {
  filterType = type;
  document.querySelectorAll('.tab').forEach(t => {
    t.classList.remove('active-all', 'active-teammate', 'active-projects');
    if (t.dataset.filter === type) {
      t.classList.add(type === 'teammate' ? 'active-teammate' : type === 'projects' ? 'active-projects' : 'active-all');
    }
  });
  renderConvList();
}

document.addEventListener('DOMContentLoaded', async () => {
  const ok = await requireLogin('messages.html');
  if (!ok) return;
  await initPage('messages');
  await loadConversations();

  const convParam = getQueryParam('conversation');
  if (convParam) await selectConv(parseInt(convParam));

  document.getElementById('convSearch')?.addEventListener('input', renderConvList);
  document.getElementById('msgInput')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') sendMessage();
  });
});
