let activeCategory = 'All';
let searchTerm = '';

async function loadSkills() {
  const params = new URLSearchParams({ action: 'list' });
  if (activeCategory !== 'All') params.set('category', activeCategory);
  if (searchTerm) params.set('search', searchTerm);

  const data = await apiFetch('skills.php?' + params.toString());
  renderStats(data.stats);
  renderGrid(data.skills);
}

async function loadAnalytics() {
  const data = await apiFetch('skills.php?action=analytics');
  renderAnalytics(data);
}

function renderStats(stats) {
  const strip = document.getElementById('statsStrip');
  if (!strip) return;
  strip.innerHTML = `
    <div class="sol-stat"><div class="sol-stat-num">${stats.skills}</div><div class="sol-stat-label">Skills</div></div>
    <div class="sol-stat"><div class="sol-stat-num">${stats.students}</div><div class="sol-stat-label">Students</div></div>
    <div class="sol-stat"><div class="sol-stat-num">${stats.gigs}</div><div class="sol-stat-label">Open Gigs</div></div>
    <div class="sol-stat"><div class="sol-stat-num">${stats.applications || 0}</div><div class="sol-stat-label">Applications</div></div>
    <div class="sol-stat"><div class="sol-stat-num">${stats.research_topics || 0}</div><div class="sol-stat-label">Research Topics</div></div>`;
}

function renderAnalytics(data) {
  const el = document.getElementById('analyticsPanel');
  if (!el) return;

  el.innerHTML = `
    <div class="analytics-grid">
      <section class="analytics-card">
        <h3>Trending Skills</h3>
        <ul class="analytics-list">
          ${data.trending.map((s, i) => `
            <li>
              <span class="analytics-rank">${i + 1}</span>
              <span class="analytics-icon">${s.icon || '•'}</span>
              <div class="analytics-item-body">
                <strong>${escapeHtml(s.name)}</strong>
                <span>${s.gig_count} gigs · ${s.expert_count} experts</span>
              </div>
              <a href="index.html?skill=${encodeURIComponent(s.name)}" class="analytics-link">View →</a>
            </li>
          `).join('')}
        </ul>
      </section>

      <section class="analytics-card">
        <h3>Popular Open Projects</h3>
        <ul class="analytics-list">
          ${data.popular_projects.map(p => `
            <li>
              <div class="analytics-item-body">
                <strong>${escapeHtml(p.title)}</strong>
                <span>${escapeHtml(p.host_name)} · ${escapeHtml(p.type)}${p.topic ? ' · ' + escapeHtml(p.topic) : ''}</span>
                <span class="tag" style="margin-top:4px">${escapeHtml(p.skills || '')}</span>
              </div>
            </li>
          `).join('')}
        </ul>
      </section>

      <section class="analytics-card">
        <h3>Research & Paper Topics</h3>
        <ul class="analytics-list">
          ${data.research_topics.map(r => `
            <li>
              <div class="analytics-item-body">
                <strong>${escapeHtml(r.title)}</strong>
                <span>${escapeHtml(r.skill_name || 'General')} · ${r.popularity} students interested</span>
                <span class="text-muted" style="font-size:0.8rem">${escapeHtml(r.description)}</span>
              </div>
              ${r.skill_name ? `<a href="index.html?skill=${encodeURIComponent(r.skill_name)}" class="analytics-link">Gigs →</a>` : ''}
            </li>
          `).join('')}
        </ul>
      </section>

      <section class="analytics-card">
        <h3>Activity by Category</h3>
        <div class="category-bars">
          ${data.category_breakdown.map(c => {
            const max = Math.max(...data.category_breakdown.map(x => x.gigs), 1);
            const pct = Math.round((c.gigs / max) * 100);
            return `
              <div class="category-bar-row">
                <span class="category-label">${escapeHtml(c.category)}</span>
                <div class="category-bar-track"><div class="category-bar-fill" style="width:${pct}%"></div></div>
                <span class="category-count">${c.gigs} gigs</span>
              </div>`;
          }).join('')}
        </div>
      </section>
    </div>`;
}

function renderGrid(skills) {
  const grid = document.getElementById('solGrid');
  const empty = document.getElementById('solEmpty');
  const count = document.getElementById('resultCount');
  if (!grid) return;

  count.textContent = `Showing ${skills.length} skill${skills.length !== 1 ? 's' : ''}`;

  if (skills.length === 0) {
    grid.innerHTML = '';
    empty.classList.add('show');
    return;
  }
  empty.classList.remove('show');

  grid.innerHTML = skills.map(s => `
    <a href="index.html?skill=${encodeURIComponent(s.name)}" class="sol-card" style="--card-accent:${s.accent_color}">
      <div class="sol-card-top">
        <div class="sol-card-icon" style="background:${s.accent_color}22">${s.icon}</div>
        <div>
          <div class="sol-card-title">${escapeHtml(s.name)}</div>
          <div class="sol-card-category" style="color:${s.accent_color}">${escapeHtml(s.category)}</div>
        </div>
      </div>
      <p class="sol-card-desc">${escapeHtml(s.description)}</p>
      ${s.tools ? `<p class="sol-card-tools"><strong>Tools:</strong> ${escapeHtml(s.tools)}</p>` : ''}
      ${s.learning_path ? `<p class="sol-card-tools"><strong>Learn:</strong> ${escapeHtml(s.learning_path)}</p>` : ''}
      <div class="sol-card-stats">
        <span><strong>${s.gig_count}</strong> gigs</span>
        <span><strong>${s.expert_count}</strong> experts</span>
        <span><strong>${s.application_count || 0}</strong> apps</span>
      </div>
      <div class="sol-card-footer"><span class="sol-card-btn">Browse ${s.gig_count} opportunities →</span></div>
    </a>
  `).join('');
}

document.addEventListener('DOMContentLoaded', async () => {
  await initPage('solutions');

  document.querySelectorAll('.sol-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      activeCategory = tab.dataset.cat;
      document.querySelectorAll('.sol-tab').forEach(t => t.classList.toggle('active', t.dataset.cat === activeCategory));
      document.getElementById('sectionHeading').textContent =
        activeCategory === 'All' ? 'All Skill Solutions' : activeCategory + ' Solutions';
      loadSkills();
    });
  });

  const heroSearch = document.getElementById('heroSearch');
  heroSearch?.addEventListener('input', () => { searchTerm = heroSearch.value; loadSkills(); });
  document.getElementById('heroSearchBtn')?.addEventListener('click', () => {
    searchTerm = heroSearch.value;
    loadSkills();
  });

  await loadAnalytics();
  await loadSkills();
});
