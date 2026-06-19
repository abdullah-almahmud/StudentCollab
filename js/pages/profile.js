let profileData = null;
let allSkills = [];
let selectedPortfolioSkills = new Set();
let editMode = false;

async function loadProfile() {
  const id = getQueryParam('id');
  const url = id ? 'users.php?action=profile&id=' + id : 'users.php?action=profile';
  try {
    const data = await apiFetch(url);
    profileData = data.profile;
    renderProfile();
  } catch (e) {
    if (!id && e.message.includes('Authentication')) {
      window.location.href = 'login.html?redirect=profile.html';
    } else {
      showToast(e.message);
    }
  }
}

function renderProfile() {
  const p = profileData;
  document.getElementById('pageTitle').textContent = p.is_own ? 'My Profile' : p.name;
  document.getElementById('profileName').textContent = p.name;
  document.getElementById('profileMajor').textContent = p.major || '—';
  document.getElementById('profileBio').textContent = p.bio || 'No bio yet.';
  document.getElementById('profileEmail').textContent = p.is_own ? p.email : 'Hidden';
  document.getElementById('profileAvatar').textContent = p.initials;
  document.getElementById('profileAvatar').style.background = p.avatar_color;
  document.getElementById('profileRole').textContent = p.is_own ? 'Host / Teammate' : 'Teammate';

  document.getElementById('skillBadges').innerHTML = (p.skills || []).map(s =>
    `<span class="profile-skill-badge">${escapeHtml(s.name)}</span>`
  ).join('') || '<span class="text-muted">No skills listed</span>';

  const links = [];
  if (p.github_url) links.push(`<a href="${escapeHtml(p.github_url)}" target="_blank" rel="noopener">GitHub</a>`);
  if (p.linkedin_url) links.push(`<a href="${escapeHtml(p.linkedin_url)}" target="_blank" rel="noopener">LinkedIn</a>`);
  if (p.website_url) links.push(`<a href="${escapeHtml(p.website_url)}" target="_blank" rel="noopener">Website</a>`);
  document.getElementById('profileLinks').innerHTML = links.join('') || '<span class="text-muted">No links added</span>';

  renderCoreMembers();
  renderPortfolio();
  renderCoreActions();

  document.getElementById('editToggleBtn').classList.toggle('hidden', !p.is_own);
  document.getElementById('addPortfolioSection').classList.toggle('hidden', !p.is_own || editMode);
}

function renderCoreMembers() {
  const list = document.getElementById('coreMembersList');
  const members = profileData.core_members || [];
  list.innerHTML = members.length ? members.map(m => `
    <a href="profile.html?id=${m.id}" class="core-member-chip">
      <div class="core-member-avatar" style="background:${m.avatar_color}">${escapeHtml(m.initials)}</div>
      <span>${escapeHtml(m.name.split(' ')[0])}</span>
      <small>${escapeHtml(m.major)}</small>
    </a>
  `).join('') : '<p class="text-muted">No core teammates yet. Accept applications or send requests to build your team.</p>';

  const pendingSec = document.getElementById('pendingRequestsSection');
  const pending = profileData.pending_core_requests || [];
  pendingSec.classList.toggle('hidden', !profileData.is_own || pending.length === 0);
  document.getElementById('pendingRequestsList').innerHTML = pending.map(r => `
    <div class="pending-request-row">
      <span>${escapeHtml(r.name)} wants to be a core teammate</span>
      <div style="display:flex;gap:0.35rem">
        <button class="btn btn-primary btn-sm" onclick="respondCore(${r.id}, true)">Accept</button>
        <button class="btn btn-secondary btn-sm" onclick="respondCore(${r.id}, false)">Decline</button>
      </div>
    </div>
  `).join('');
}

function renderCoreActions() {
  const el = document.getElementById('coreMemberActions');
  const btn = document.getElementById('requestCoreBtn');
  if (profileData.is_own || !currentUser) {
    el.classList.add('hidden');
    return;
  }
  el.classList.remove('hidden');
  const st = profileData.core_request_status;
  if (st === 'pending') {
    btn.textContent = 'Request Pending';
    btn.disabled = true;
  } else if (st === 'accepted') {
    btn.textContent = 'Core Teammate';
    btn.disabled = true;
  } else {
    btn.textContent = 'Request as Core Teammate';
    btn.disabled = false;
  }
}

function renderPortfolio() {
  const p = profileData;
  document.getElementById('portfolioList').innerHTML = (p.portfolio || []).map(item => `
    <div class="portfolio-item">
      <div style="display:flex;justify-content:space-between;align-items:start;gap:0.5rem">
        <h4>${escapeHtml(item.title)}</h4>
        <span class="badge badge-${item.status === 'completed' ? 'accepted' : 'pending'}">${item.status === 'completed' ? 'Completed' : 'In Progress'}</span>
      </div>
      <p>${escapeHtml(item.description)}</p>
      <div>${(item.skills || []).map(s => `<span class="tag">${escapeHtml(s)}</span>`).join('')}</div>
      ${item.project_url ? `<a href="${escapeHtml(item.project_url)}" target="_blank" rel="noopener">View Project →</a>` : ''}
      ${p.is_own ? `<button class="btn btn-danger btn-sm" style="margin-top:0.75rem" onclick="deletePortfolio(${item.id})">Remove</button>` : ''}
    </div>
  `).join('') || '<p class="text-muted">No portfolio projects yet.</p>';
}

function toggleEditMode() {
  editMode = !editMode;
  document.getElementById('viewModeInfo').classList.toggle('hidden', editMode);
  document.getElementById('editModeForm').classList.toggle('hidden', !editMode);
  document.getElementById('editToggleBtn').textContent = editMode ? 'Cancel Edit' : 'Edit Profile';
  document.getElementById('addPortfolioSection').classList.toggle('hidden', !profileData.is_own || editMode);

  if (editMode) {
    document.getElementById('editName').value = profileData.name;
    document.getElementById('editMajor').value = profileData.major || '';
    document.getElementById('editBio').value = profileData.bio || '';
    document.getElementById('editGithub').value = profileData.github_url || '';
    document.getElementById('editLinkedin').value = profileData.linkedin_url || '';
    document.getElementById('editWebsite').value = profileData.website_url || '';
  }
}

async function saveProfile() {
  try {
    await apiFetch('users.php?action=update', {
      method: 'POST',
      body: {
        name: document.getElementById('editName').value,
        major: document.getElementById('editMajor').value,
        bio: document.getElementById('editBio').value,
        github_url: document.getElementById('editGithub').value,
        linkedin_url: document.getElementById('editLinkedin').value,
        website_url: document.getElementById('editWebsite').value,
      },
    });
    showToast('Profile updated');
    editMode = false;
    await loadProfile();
  } catch (e) {
    showToast(e.message);
  }
}

async function requestCoreMember() {
  try {
    await apiFetch('users.php?action=core_request', {
      method: 'POST',
      body: { user_id: profileData.id },
    });
    showToast('Core teammate request sent');
    await loadProfile();
  } catch (e) {
    showToast(e.message);
  }
}

async function respondCore(requestId, accept) {
  try {
    await apiFetch('users.php?action=core_respond', {
      method: 'POST',
      body: { request_id: requestId, accept },
    });
    showToast(accept ? 'Core teammate added' : 'Request declined');
    await loadProfile();
  } catch (e) {
    showToast(e.message);
  }
}

async function loadSkillsForForm() {
  const data = await apiFetch('skills.php?action=all');
  allSkills = data.skills;
  renderPortfolioSkillTags();
}

function renderPortfolioSkillTags() {
  document.getElementById('portfolioSkillTags').innerHTML = allSkills.map(s =>
    `<button type="button" class="skill-pill${selectedPortfolioSkills.has(s.id) ? ' active' : ''}" onclick="togglePortfolioSkill(${s.id})">${escapeHtml(s.name)}</button>`
  ).join('');
}

function togglePortfolioSkill(id) {
  selectedPortfolioSkills.has(id) ? selectedPortfolioSkills.delete(id) : selectedPortfolioSkills.add(id);
  renderPortfolioSkillTags();
}

async function addPortfolio() {
  const title = document.getElementById('portTitle').value.trim();
  const description = document.getElementById('portDesc').value.trim();
  const projectUrl = document.getElementById('portUrl').value.trim();
  const status = document.getElementById('portStatus').value;
  if (!title || !description) {
    showToast('Title and description required');
    return;
  }
  try {
    await apiFetch('users.php?action=portfolio', {
      method: 'POST',
      body: { title, description, status, project_url: projectUrl, skill_ids: [...selectedPortfolioSkills] },
    });
    document.getElementById('portTitle').value = '';
    document.getElementById('portDesc').value = '';
    document.getElementById('portUrl').value = '';
    selectedPortfolioSkills.clear();
    renderPortfolioSkillTags();
    showToast('Portfolio item added');
    await loadProfile();
  } catch (e) {
    showToast(e.message);
  }
}

async function deletePortfolio(id) {
  if (!confirm('Remove this portfolio item?')) return;
  try {
    await apiFetch('users.php?action=portfolio&id=' + id, { method: 'DELETE' });
    await loadProfile();
  } catch (e) {
    showToast(e.message);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await initPage('profile');
  await loadProfile();
  if (profileData?.is_own) await loadSkillsForForm();
});
