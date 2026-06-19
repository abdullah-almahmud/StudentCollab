const NAV_PAGES = {
  home: { href: 'index.html', label: 'Opportunities' },
  solutions: { href: 'solutions.html', label: 'Solutions' },
  members: { href: 'members.html', label: 'Members' },
  messages: { href: 'messages.html', label: 'Messages' },
  profile: { href: 'profile.html', label: 'Profile' },
  applications: { href: 'applications.html', label: 'Applications' },
  post: { href: 'post.html', label: 'Post' },
};

function renderHeader(activePage) {
  const mount = document.getElementById('site-header');
  if (!mount) return;

  const authSection = currentUser
    ? `<span class="header-user-name">${escapeHtml(currentUser.name.split(' ')[0])}</span>
       <a href="#" class="header-text-link" onclick="logout(); return false;">Log Out</a>`
    : `<a href="login.html" class="header-text-link">Log In</a>
       <a href="signup.html" class="header-text-link">Sign Up</a>`;

  mount.innerHTML = `
    <header class="header">
      <div class="header-container">
        <div class="header-left">
          <a href="index.html" class="logo">
            <svg class="logo-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
            </svg>
            <span class="logo-text">StudentCollab</span>
          </a>
          <nav class="nav-menu">
            <a href="index.html" class="nav-link${activePage === 'home' ? ' active' : ''}">Opportunities</a>
            <a href="solutions.html" class="nav-link${activePage === 'solutions' ? ' active' : ''}">Solutions</a>
            <a href="members.html" class="nav-link${activePage === 'members' ? ' active' : ''}">Members</a>
            ${currentUser ? `<a href="applications.html" class="nav-link${activePage === 'applications' ? ' active' : ''}">Applications</a>` : ''}
          </nav>
        </div>
        <div class="header-right">
          <a href="messages.html" class="header-icon-link${activePage === 'messages' ? ' active' : ''}" title="Messages">
            <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <span class="header-icon-label">Messages</span>
          </a>
          <a href="profile.html" class="header-icon-link${activePage === 'profile' ? ' active' : ''}" title="Profile">
            <svg class="header-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
            <span class="header-icon-label">Profile</span>
          </a>
          <div class="header-divider"></div>
          ${authSection}
          <a href="post.html" class="header-cta-btn">Post</a>
        </div>
      </div>
    </header>`;
}

async function initPage(activePage) {
  await loadCurrentUser();
  renderHeader(activePage);
}
