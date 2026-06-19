let allGigs = [];
let activeType = 'all';
let activeSkill = '';
let applyGigId = null;
let applySelectedSkills = new Set();
let applySelectedPortfolio = new Set();

async function loadGigs() {
  const searchInput = document.getElementById('searchInput');
  const skillParam = getQueryParam('skill');
  const searchParam = getQueryParam('search');

  let search = searchInput?.value || searchParam || '';
  if (skillParam && !searchInput?.value) {
    activeSkill = skillParam;
    search = skillParam;
    if (searchInput) searchInput.value = skillParam;
  }

  const params = new URLSearchParams();
  if (search) params.set('search', search);
  if (activeType !== 'all') params.set('type', activeType);
  if (activeSkill) params.set('skill', activeSkill);

  const data = await apiFetch('gigs.php?action=list&' + params.toString());
  allGigs = data.gigs;
  renderGigs();
}

function renderGigs() {
  const grid = document.getElementById('gigGrid');
  if (!grid) return;

  if (allGigs.length === 0) {
    grid.innerHTML = `<div class="empty-state"><p>No opportunities match your search.</p><a href="post.html" class="btn btn-primary" style="margin-top:1rem;display:inline-flex">Post an Opportunity</a></div>`;
    return;
  }

  grid.innerHTML = allGigs.map(g => `
    <article class="gig-card">
      <div class="gig-card-header">
        <span class="badge badge-${g.type === 'freelance' ? 'freelance' : 'collab'}">${g.type === 'freelance' ? 'Freelance' : 'Collaboration'}</span>
        <span class="badge badge-${g.status === 'open' ? 'open' : 'filled'}">${g.status === 'open' ? 'Open' : 'Filled'}</span>
      </div>
      <h3>${escapeHtml(g.title)}</h3>
      ${g.topic ? `<p class="gig-topic">${escapeHtml(g.topic)}</p>` : ''}
      <p class="gig-card-desc">${escapeHtml(g.description)}</p>
      <div>${(g.skills || []).map(s => `<span class="tag">${escapeHtml(s.name)}</span>`).join('')}</div>
      <div class="gig-meta">
        ${g.budget ? `<div><strong>Budget:</strong> ${escapeHtml(g.budget)}</div>` : ''}
        ${g.duration ? `<div><strong>Duration:</strong> ${escapeHtml(g.duration)}</div>` : ''}
        ${g.team_size ? `<div><strong>Team:</strong> ${escapeHtml(g.team_size)}</div>` : ''}
        <div><strong>Host:</strong> ${escapeHtml(g.owner_name)} · ${formatDate(g.created_at)}</div>
      </div>
      <div class="gig-card-actions">
        ${g.status === 'open' ? `<button class="btn btn-primary" onclick="openApplyModal(${g.id})">Apply Now</button>` : `<span class="text-muted" style="font-size:0.85rem">Team filled</span>`}
      </div>
    </article>
  `).join('');
}

async function loadSkillPills() {
  const data = await apiFetch('skills.php?action=all');
  const wrap = document.getElementById('skillPills');
  if (!wrap) return;
  wrap.innerHTML = data.skills.slice(0, 14).map(s =>
    `<button type="button" class="skill-pill${activeSkill === s.name ? ' active' : ''}" onclick="filterSkill('${escapeHtml(s.name).replace(/'/g, "\\'")}')">${escapeHtml(s.name)}</button>`
  ).join('');
}

function filterSkill(name) {
  activeSkill = activeSkill === name ? '' : name;
  const input = document.getElementById('searchInput');
  if (input) input.value = activeSkill;
  loadSkillPills();
  loadGigs();
}

function setTab(type) {
  activeType = type;
  document.querySelectorAll('.home-tab').forEach(t => t.classList.toggle('active', t.dataset.type === type));
  loadGigs();
}

async function openApplyModal(id) {
  if (!currentUser) {
    window.location.href = 'login.html?redirect=' + encodeURIComponent(window.location.pathname + window.location.search);
    return;
  }
  applyGigId = id;
  applySelectedSkills.clear();
  applySelectedPortfolio.clear();

  const gig = allGigs.find(g => g.id === id);
  document.getElementById('applyGigTitle').textContent = gig?.title || 'Opportunity';
  document.getElementById('applyMessage').value = '';

  try {
    const data = await apiFetch('applications.php?action=form_data&gig_id=' + id);
    const skillsEl = document.getElementById('applySkills');
    skillsEl.innerHTML = data.skills.map(s => {
      const relevant = data.gig_skill_ids.includes(s.id);
      const pre = relevant ? ' preselect' : '';
      if (relevant) applySelectedSkills.add(s.id);
      return `<button type="button" class="skill-pill apply-skill${applySelectedSkills.has(s.id) ? ' active' : ''}${pre}" data-id="${s.id}" onclick="toggleApplySkill(${s.id})">${escapeHtml(s.name)}</button>`;
    }).join('') || '<p class="text-muted">Add skills to your profile first.</p>';

    const portEl = document.getElementById('applyPortfolio');
    portEl.innerHTML = data.portfolio.length ? data.portfolio.map(p => `
      <label class="apply-portfolio-item">
        <input type="checkbox" value="${p.id}" ${applySelectedPortfolio.has(p.id) ? 'checked' : ''} onchange="toggleApplyPortfolio(${p.id}, this.checked)">
        <span><strong>${escapeHtml(p.title)}</strong><br><span class="text-muted">${escapeHtml(p.description.slice(0, 80))}…</span></span>
      </label>
    `).join('') : '<p class="text-muted">No portfolio projects yet — add some on your profile.</p>';
  } catch (e) {
    showToast(e.message);
  }

  document.getElementById('applyModal').classList.add('open');
}

function toggleApplySkill(id) {
  applySelectedSkills.has(id) ? applySelectedSkills.delete(id) : applySelectedSkills.add(id);
  document.querySelectorAll('.apply-skill').forEach(el => {
    el.classList.toggle('active', applySelectedSkills.has(parseInt(el.dataset.id)));
  });
}

function toggleApplyPortfolio(id, checked) {
  checked ? applySelectedPortfolio.add(id) : applySelectedPortfolio.delete(id);
}

function closeApplyModal() {
  document.getElementById('applyModal').classList.remove('open');
  applyGigId = null;
}

async function submitApplication() {
  const message = document.getElementById('applyMessage').value.trim();
  if (!message) {
    showToast('Please write a short proposal message');
    return;
  }
  try {
    await apiFetch('applications.php?action=apply', {
      method: 'POST',
      body: {
        gig_id: applyGigId,
        message,
        skill_ids: [...applySelectedSkills],
        portfolio_ids: [...applySelectedPortfolio],
      },
    });
    closeApplyModal();
    showToast('Application sent! View it under Applications.');
  } catch (e) {
    showToast(e.message);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  await initPage('home');
  await loadSkillPills();
  await loadGigs();
  document.getElementById('searchInput')?.addEventListener('input', debounce(loadGigs, 300));
  document.getElementById('searchBtn')?.addEventListener('click', loadGigs);
});

function debounce(fn, ms) {
  let t;
  return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
}
