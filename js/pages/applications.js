let activeTab = 'sent';

async function loadApplications() {
  const data = await apiFetch('applications.php?action=list&tab=' + activeTab);
  renderApplications(data.applications);
}

function renderApplications(apps) {
  const list = document.getElementById('appsList');
  if (apps.length === 0) {
    list.innerHTML = `<div class="empty-state"><p>No ${activeTab === 'received' ? 'received' : 'sent'} applications yet.</p></div>`;
    return;
  }

  list.innerHTML = apps.map(a => `
    <div class="app-card">
      <div class="app-card-header">
        <div>
          <h3>${escapeHtml(a.gig_title)}</h3>
          <span class="badge badge-${a.gig_type === 'freelance' ? 'freelance' : 'collab'}">${a.gig_type}</span>
        </div>
        <span class="badge badge-${a.status}">${a.status.charAt(0).toUpperCase() + a.status.slice(1)}</span>
      </div>
      <div class="app-meta">
        ${activeTab === 'received'
          ? `From <strong>${escapeHtml(a.applicant_name)}</strong> · ${escapeHtml(a.applicant_major)}`
          : `Host: <strong>${escapeHtml(a.owner_name)}</strong>`}
        · ${formatDate(a.created_at)}
      </div>
      <div class="app-message">${escapeHtml(a.message)}</div>
      ${(a.highlight_skills || []).length ? `
        <div class="app-highlights"><span class="meta-label" style="margin-top:0">Highlighted Skills</span>
        ${a.highlight_skills.map(s => `<span class="tag">${escapeHtml(s.name)}</span>`).join('')}</div>` : ''}
      ${(a.highlight_projects || []).length ? `
        <div class="app-highlights"><span class="meta-label" style="margin-top:0.5rem">Portfolio Shown</span>
        ${a.highlight_projects.map(p => `<span class="tag">${escapeHtml(p.title)}</span>`).join('')}</div>` : ''}
      <div class="app-actions">
        ${activeTab === 'received' && a.status === 'pending' ? `
          <button class="btn btn-primary btn-sm" onclick="updateApp(${a.id}, 'accepted')">Accept Teammate</button>
          <button class="btn btn-danger btn-sm" onclick="updateApp(${a.id}, 'rejected')">Reject</button>
        ` : ''}
        ${a.status === 'accepted' && a.conversation_id ? `
          <a href="messages.html?conversation=${a.conversation_id}" class="btn btn-primary btn-sm">Open Project Chat</a>
        ` : ''}
      </div>
    </div>
  `).join('');
}

async function updateApp(id, status) {
  try {
    const data = await apiFetch('applications.php?action=update', {
      method: 'POST',
      body: { id, status },
    });
    showToast(status === 'accepted' ? 'Teammate accepted! Project chat created.' : 'Application rejected.');
    if (data.conversation_id) {
      setTimeout(() => window.location.href = 'messages.html?conversation=' + data.conversation_id, 1000);
    } else {
      loadApplications();
    }
  } catch (e) {
    showToast(e.message);
  }
}

function setTab(tab) {
  activeTab = tab;
  document.querySelectorAll('.apps-tab').forEach(t => t.classList.toggle('active', t.dataset.tab === tab));
  loadApplications();
}

document.addEventListener('DOMContentLoaded', async () => {
  const ok = await requireLogin('applications.html');
  if (!ok) return;
  await initPage('applications');
  await loadApplications();
});
