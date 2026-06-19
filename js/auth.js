let currentUser = null;

async function loadCurrentUser() {
  try {
    const data = await apiFetch('auth.php?action=me');
    currentUser = data.user;
  } catch {
    currentUser = null;
  }
  return currentUser;
}

async function requireLogin(redirectTo) {
  await loadCurrentUser();
  if (!currentUser) {
    window.location.href = 'login.html?redirect=' + encodeURIComponent(redirectTo || window.location.pathname);
    return false;
  }
  return true;
}

async function logout() {
  await apiFetch('auth.php?action=logout', { method: 'POST', body: {} });
  window.location.href = 'index.html';
}
