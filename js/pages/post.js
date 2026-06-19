let activeTab = 'sent';
let allSkills = [];
let selectedSkillIds = new Set();
let currentType = 'freelance';

async function loadAllSkills() {
  const data = await apiFetch('skills.php?action=all');
  allSkills = data.skills;
  renderSkillTags();
}

function renderSkillTags() {
  const wrap = document.getElementById('skillTags');
  wrap.innerHTML = allSkills.map(s =>
    `<button type="button" class="skill-tag-btn${selectedSkillIds.has(s.id) ? ' on' : ''}" onclick="toggleSkill(${s.id})">${escapeHtml(s.name)}</button>`
  ).join('');
}

function toggleSkill(id) {
  selectedSkillIds.has(id) ? selectedSkillIds.delete(id) : selectedSkillIds.add(id);
  renderSkillTags();
}

function selectType(type) {
  currentType = type;
  document.getElementById('card-freelance').classList.toggle('active', type === 'freelance');
  document.getElementById('card-collaboration').classList.toggle('active', type === 'collaboration');
  document.getElementById('freelanceFields').classList.toggle('hidden', type !== 'freelance');
  document.getElementById('collabFields').classList.toggle('hidden', type !== 'collaboration');
}

async function submitPost() {
  const title = document.getElementById('title').value.trim();
  const description = document.getElementById('description').value.trim();
  if (!title || !description) {
    showToast('Title and description are required');
    return;
  }

  const body = {
    title,
    description,
    type: currentType,
    skill_ids: [...selectedSkillIds],
    budget: document.getElementById('budget')?.value.trim() || '',
    duration: document.getElementById('duration')?.value.trim() || '',
    team_size: document.getElementById('teamSize')?.value.trim() || '',
  };

  try {
    await apiFetch('gigs.php?action=create', { method: 'POST', body });
    showToast('Opportunity posted!');
    setTimeout(() => window.location.href = 'index.html', 800);
  } catch (e) {
    showToast(e.message);
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const ok = await requireLogin('post.html');
  if (!ok) return;
  await initPage('post');
  await loadAllSkills();
});
