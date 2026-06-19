let activeSkill = '';

async function loadMembers() {
  const search = document.getElementById('memberSearch')?.value || '';
  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (activeSkill) params.set('skill', activeSkill);

  const data = await apiFetch('users.php?action=list&' + params.toString());
  renderMembers(data.users);
}

function renderMembers(users) {
  const grid = document.getElementById('memberGrid');
  if (!grid) return;

  if (users.length === 0) {
    grid.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><p>No members found.</p></div>';
    return;
  }

  grid.innerHTML = users.map(u => `
    <article class="member-card">
      <div class="member-top">
        <div class="member-avatar" style="background:${u.avatar_color}">${escapeHtml(u.initials)}</div>
        <div>
          <div class="member-name">${escapeHtml(u.name)}</div>
          <div class="member-major">${escapeHtml(u.major)}</div>
        </div>
      </div>
      <p class="member-bio">${escapeHtml(u.bio || 'No bio yet.')}</p>
      <div>${(u.skills || []).map(s => `<span class="tag">${escapeHtml(s.name)}</span>`).join('')}</div>
      <div class="member-actions">
        <a href="profile.html?id=${u.id}" class="btn btn-secondary">View Profile</a>
        <button class="btn btn-primary" onclick="messageUser(${u.id})">Message</button>
      </div>
    </article>
  `).join('');
}

async function loadSkillPills() {
  const data = await apiFetch('skills.php?action=all');
  const wrap = document.getElementById('skillPills');
  wrap.innerHTML = data.skills.slice(0, 10).map(s =>
    `<button type="button" class="skill-pill${activeSkill === s.name ? ' active' : ''}" onclick="filterMemberSkill('${escapeHtml(s.name).replace(/'/g, "\\'")}')">${escapeHtml(s.name)}</button>`
  ).join('');
}

function filterMemberSkill(name) {
  activeSkill = activeSkill === name ? '' : name;
  loadSkillPills();
  loadMembers();
}

async function messageUser(userId) {
  if (!currentUser) {
    window.location.href = 'login.html?redirect=members.html';
    return;
  }
  try {
    const data = await apiFetch('messages.php?action=start', { method: 'POST', body: { user_id: userId } });
    window.location.href = 'messages.html?conversation=' + data.conversation_id;
  } catch (e) {
    showToast(e.message);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await initPage('members');
  await loadSkillPills();
  await loadMembers();
  document.getElementById('memberSearch')?.addEventListener('input', debounce(loadMembers, 300));
});

function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}
