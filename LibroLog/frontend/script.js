
const API_BASE_URL = 'http://localhost:5000/api';
// const API_BASE_URL = 'https://your-production-api.com/api'; // Production

// ===== STATE MANAGEMENT =====
const appState = {
  isLoggedIn: false,
  currentUser: null,
  token: null,
  books: [],
  currentBookId: null,
  filteredBooks: []
};

// ===== INITIALIZATION =====
document.addEventListener('DOMContentLoaded', () => {
  console.log('LibroLog App Initialized');
  checkAuth();
  attachEventListeners();
  setupFormValidation();
});

// ===== AUTHENTICATION FUNCTIONS ===== 
function checkAuth() {
  const token = localStorage.getItem('authToken');
  const username = localStorage.getItem('username');

  if (token && username) {
    appState.token = token;
    appState.isLoggedIn = true;
    appState.currentUser = username;

    showSection('home');
    updateWelcomeMessage(username);
    loadBooks();
  } else {
    showSection('login');
  }
}

// Login
function handleLogin(event) {
  event.preventDefault();

  const email = document.getElementById('login-email').value.trim();
  const password = document.getElementById('login-password').value;

  if (!email || !password) {
    showError('login-error', 'Email and password are required');
    return;
  }

  document.getElementById('login-loading').classList.remove('hidden');
  document.getElementById('login-error').classList.add('hidden');

  fetch(`${API_BASE_URL}/auth/login`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      email,
      password
    })
  })
    .then(async response => {

    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.message || "Login failed.");
    }

    return data;
})
    .then(data => {
      // Success! Save token and username
      localStorage.setItem('authToken', data.token);
      localStorage.setItem('username', data.username);

      appState.token = data.token;
      appState.isLoggedIn = true;
      appState.currentUser = data.username;

      document.getElementById('login-form').reset();
      showSection('home');
      updateWelcomeMessage(data.username);
      loadBooks();
    })
    .catch(error => {
      console.error('Login error:', error);
      showError('login-error', error.message);
    })
    .finally(() => {
      document.getElementById('login-loading').classList.add('hidden');
    });
}

// Register
function handleRegister(event) {
  event.preventDefault();

  const username = document.getElementById('register-username').value.trim();
  const email = document.getElementById('register-email').value.trim();
  const password = document.getElementById('register-password').value;
  const confirmPassword = document.getElementById('register-confirm').value;

  if (!username || !email || !password || !confirmPassword) {
    showError('register-error', 'All fields are required');
    return;
  }

  if (username.length < 3) {
    showError('register-error', 'Username must be at least 3 characters');
    return;
  }

  if (password.length < 6) {
    showError('register-error', 'Password must be at least 6 characters');
    return;
  }

  if (password !== confirmPassword) {
    showError('register-error', 'Passwords do not match');
    return;
  }

  document.getElementById('register-loading').classList.remove('hidden');
  document.getElementById('register-error').classList.add('hidden');

  fetch(`${API_BASE_URL}/auth/register`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      username,
      email,
      password,
      confirmPassword
    })
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Registration failed. Email might be already in use.');
      }
      return response.json();
    })
    .then(() => {
      alert('Account created successfully! Please login with your credentials.');
      document.getElementById('register-form').reset();
      showSection('login');
    })
    .catch(error => {
      console.error('Register error:', error);
      showError('register-error', error.message);
    })
    .finally(() => {
      document.getElementById('register-loading').classList.add('hidden');
    });
}

// Logout
function handleLogout() {
  if (!confirm('Are you sure you want to logout?')) {
    return;
  }

  localStorage.removeItem('authToken');
  localStorage.removeItem('username');

  appState.token = null;
  appState.isLoggedIn = false;
  appState.currentUser = null;
  appState.books = [];

  document.getElementById('login-form').reset();
  document.getElementById('register-form').reset();
  showSection('login');
}

// ===== BOOK MANAGEMENT FUNCTIONS =====
// Load books
function loadBooks() {
  if (!appState.token) {
    console.error('No token available');
    return;
  }

  document.getElementById('books-loading').classList.remove('hidden');
  document.getElementById('books-grid').innerHTML = '';

  fetch(`${API_BASE_URL}/books`, {
    method: 'GET',
    headers: {
      'Authorization': `Bearer ${appState.token}`,
      'Content-Type': 'application/json'
    }
  })
    .then(response => {
      if (response.status === 403) {
        handleLogout();
        throw new Error('Session expired. Please login again.');
      }
      if (!response.ok) {
        throw new Error('Failed to load books');
      }
      return response.json();
    })
    .then(data => {
      appState.books = data.books || [];
      appState.filteredBooks = appState.books;
      renderBooks();
      updateStatistics();
    })
    .catch(error => {
      console.error('Load books error:', error);
      document.getElementById('books-grid').innerHTML = `<div class="error-alert show">${error.message}</div>`;
    })
    .finally(() => {
      document.getElementById('books-loading').classList.add('hidden');
    });
}

// Render book cards
function renderBooks() {
  const grid = document.getElementById('books-grid');
  const empty = document.getElementById('empty-books');

  grid.innerHTML = '';

  if (appState.filteredBooks.length === 0) {
    empty.classList.remove('hidden');
    return;
  }

  empty.classList.add('hidden');

  appState.filteredBooks.forEach(book => {
    const card = createBookCard(book);
    grid.appendChild(card);
  });
}

// Build single book card
function createBookCard(book) {
  const card = document.createElement('div');
  card.className = 'book-card';
  const statusClass = `status-${book.status}`;
  const progress = book.pages && book.currentPage
    ? Math.round((book.currentPage / book.pages) * 100)
    : 0;
  const stars = book.rating
    ? '⭐'.repeat(Math.round(book.rating))
    : 'Not rated';

  card.innerHTML = `
    <div class="book-cover">📖</div>
    <div class="book-body">
      <h3 class="book-title">${book.title}</h3>
      <p class="book-author">${book.author}</p>
      <span class="book-status ${statusClass}">${book.status.replace('-', ' ')}</span>
      <div class="book-rating">${stars}</div>
      ${book.currentPage && book.pages
        ? `<small>Progress: ${progress}% (${book.currentPage}/${book.pages})</small>`
        : ''}
      <div class="book-actions">
        <button class="btn btn-secondary" onclick="editBook('${book._id}')">Edit</button>
        <button class="btn btn-danger" onclick="deleteBook('${book._id}', '${book.title}')">Delete</button>
      </div>
    </div>
  `;

  return card;
}

// Filter books by status
function filterBooks() {
  const statusFilter = document.getElementById('status-filter').value;

  if (statusFilter === 'all') {
    appState.filteredBooks = appState.books;
  } else {
    appState.filteredBooks = appState.books.filter(
      book => book.status === statusFilter
    );
  }

  renderBooks();
}

// Open add-book modal
function openAddBookModal() {
  document.getElementById('book-form').reset();
  document.getElementById('book-id').value = '';
  document.getElementById('modal-title').textContent = 'Add New Book';
  document.getElementById('delete-btn').classList.add('hidden');
  document.getElementById('book-modal').classList.remove('hidden');
}

// Open edit form for a book
function editBook(bookId) {
  const book = appState.books.find(b => b._id === bookId);
  if (!book) {
    alert('Book not found');
    return;
  }

  document.getElementById('book-id').value = book._id;
  document.getElementById('book-title').value = book.title;
  document.getElementById('book-author').value = book.author;
  document.getElementById('book-isbn').value = book.isbn || '';
  document.getElementById('book-genre').value = book.genre || '';
  document.getElementById('book-status').value = book.status;
  document.getElementById('book-pages').value = book.pages || '';
  document.getElementById('book-current-page').value = book.currentPage || '';
  document.getElementById('book-rating').value = book.rating || 0;
  document.getElementById('rating-display').textContent = book.rating || 0;
  document.getElementById('book-review').value = book.review || '';

  document.getElementById('modal-title').textContent = 'Edit Book';
  document.getElementById('delete-btn').classList.remove('hidden');
  document.getElementById('book-modal').classList.remove('hidden');
}

// Close book modal
function closeBookModal() {
  document.getElementById('book-modal').classList.add('hidden');
  document.getElementById('book-form').reset();
}

// Submit book form
function handleBookSubmit(event) {
  event.preventDefault();

  const bookId = document.getElementById('book-id').value;
  const method = bookId ? 'PUT' : 'POST';
  const url = bookId
    ? `${API_BASE_URL}/books/${bookId}`
    : `${API_BASE_URL}/books`;

  const formData = {
    title: document.getElementById('book-title').value.trim(),
    author: document.getElementById('book-author').value.trim(),
    isbn: document.getElementById('book-isbn').value.trim() || null,
    genre: document.getElementById('book-genre').value.trim() || null,
    status: document.getElementById('book-status').value,
    pages: parseInt(document.getElementById('book-pages').value) || null,
    currentPage: parseInt(document.getElementById('book-current-page').value) || null,
    rating: parseFloat(document.getElementById('book-rating').value) || null,
    review: document.getElementById('book-review').value.trim() || null
  };

  if (!formData.title || !formData.author) {
    showError('book-error', 'Title and author are required');
    return;
  }

  document.getElementById('book-loading').classList.remove('hidden');
  document.getElementById('book-error').classList.add('hidden');

  console.log(url);
  console.log(method);
  console.log(formData);

  fetch(url, {
    method,
    headers: {
      'Authorization': `Bearer ${appState.token}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(formData)
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Failed to save book');
      }
      return response.json();
    })
    .then(() => {
      closeBookModal();
      loadBooks();
      alert(bookId ? 'Book updated successfully!' : 'Book added successfully!');
    })
    .catch(error => {
      console.error('Book submit error:', error);
      showError('book-error', error.message);
    })
    .finally(() => {
      document.getElementById('book-loading').classList.add('hidden');
    });
}

// Confirm book deletion
function confirmDeleteBook() {
  const bookId = document.getElementById('book-id').value;
  const bookTitle = document.getElementById('book-title').value;

  if (!confirm(`Are you sure you want to delete "${bookTitle}"?`)) {
    return;
  }

  deleteBook(bookId, bookTitle);
}

// Delete book
function deleteBook(bookId, bookTitle) {
  if (!confirm(`Delete "${bookTitle}"?`)) {
    return;
  }

  document.getElementById('book-loading').classList.remove('hidden');

  fetch(`${API_BASE_URL}/books/${bookId}`, {
    method: 'DELETE',
    headers: {
      'Authorization': `Bearer ${appState.token}`,
      'Content-Type': 'application/json'
    }
  })
    .then(response => {
      if (!response.ok) {
        throw new Error('Failed to delete book');
      }
      return response.json();
    })
    .then(() => {
      closeBookModal();
      loadBooks();
      alert('Book deleted successfully!');
    })
    .catch(error => {
      console.error('Delete error:', error);
      alert('Failed to delete book: ' + error.message);
    })
    .finally(() => {
      document.getElementById('book-loading').classList.add('hidden');
    });
}

// ===== STATISTICS FUNCTIONS =====
// Update stats
function updateStatistics() {
  const books = appState.books;
  const totalBooks = books.length;
  const readingBooks = books.filter(b => b.status === 'reading').length;
  const completedBooks = books.filter(b => b.status === 'completed').length;
  const ratedBooks = books.filter(b => b.rating);
  const avgRating = ratedBooks.length > 0
    ? (ratedBooks.reduce((sum, b) => sum + b.rating, 0) / ratedBooks.length).toFixed(1)
    : 0;

  document.getElementById('total-books').textContent = totalBooks;
  document.getElementById('reading-books').textContent = readingBooks;
  document.getElementById('completed-books').textContent = completedBooks;
  document.getElementById('avg-rating').textContent = avgRating;

  renderStatusChart();
}

// Render status chart
function renderStatusChart() {
  const books = appState.books;
  const statusCounts = {
    'want-to-read': books.filter(b => b.status === 'want-to-read').length,
    reading: books.filter(b => b.status === 'reading').length,
    completed: books.filter(b => b.status === 'completed').length
  };
  const maxCount = Math.max(...Object.values(statusCounts), 1);
  const chartContainer = document.getElementById('status-chart');

  chartContainer.innerHTML = '';

  Object.entries(statusCounts).forEach(([status, count]) => {
    const percentage = (count / maxCount) * 100;
    const bar = document.createElement('div');
    bar.className = 'chart-bar';
    bar.innerHTML = `
      <div class="chart-bar-fill" style="height: ${percentage * 2}px;"></div>
      <span class="chart-label">${status.replace('-', ' ')}</span>
      <span class="chart-value">${count}</span>
    `;
    chartContainer.appendChild(bar);
  });
}

// ===== NAVIGATION FUNCTIONS =====
// Toggle screen sections
function showSection(sectionId) {
  document.querySelectorAll('.screen').forEach(screen => {
    screen.classList.remove('active');
  });

  const section = document.getElementById(sectionId + '-screen');
  if (section) {
    section.classList.add('active');
  }

  document.querySelectorAll('.nav-link').forEach(link => {
    link.classList.remove('active');
  });

  if (!appState.isLoggedIn && sectionId !== 'login' && sectionId !== 'register') {
    showSection('login');
    return;
  }
}

// Update welcome message
function updateWelcomeMessage(username) {
  document.getElementById('username-display').textContent = username;
}

// ===== FORM VALIDATION =====
// Setup form validation
function setupFormValidation() {
  const loginEmail = document.getElementById('login-email');
  if (loginEmail) {
    loginEmail.addEventListener('blur', () => {
      validateEmail(loginEmail, 'email-error');
    });
  }

  const regUsername = document.getElementById('register-username');
  if (regUsername) {
    regUsername.addEventListener('blur', () => {
      validateLength(regUsername, 3, 30, 'username-error');
    });
  }

  const regPassword = document.getElementById('register-password');
  if (regPassword) {
    regPassword.addEventListener('blur', () => {
      validateLength(regPassword, 6, null, 'reg-password-error');
    });
  }

  const ratingSlider = document.getElementById('book-rating');
  if (ratingSlider) {
    ratingSlider.addEventListener('input', (e) => {
      document.getElementById('rating-display').textContent = e.target.value;
    });
  }

  const review = document.getElementById('book-review');
  if (review) {
    review.addEventListener('input', (e) => {
      document.getElementById('review-count').textContent = e.target.value.length;
    });
  }
}

// Validate email format
function validateEmail(input, errorId) {
  const email = input.value.trim();
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  if (email && !emailRegex.test(email)) {
    showFieldError(errorId, 'Invalid email format');
  } else {
    clearFieldError(errorId);
  }
}

// Validate length
function validateLength(input, min, max, errorId) {
  const value = input.value.trim();

  if (min && value.length < min) {
    showFieldError(errorId, `Minimum ${min} characters required`);
  } else if (max && value.length > max) {
    showFieldError(errorId, `Maximum ${max} characters allowed`);
  } else {
    clearFieldError(errorId);
  }
}

// Show inline field error
function showFieldError(errorId, message) {
  const errorEl = document.getElementById(errorId);
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.classList.add('show');
  }
}

// Clear inline error
function clearFieldError(errorId) {
  const errorEl = document.getElementById(errorId);
  if (errorEl) {
    errorEl.textContent = '';
    errorEl.classList.remove('show');
  }
}
  
// Show general error
function showError(elementId, message) {
  const errorEl = document.getElementById(elementId);
  if (errorEl) {
    errorEl.textContent = message;
    errorEl.classList.add('show');
  }
}

// ===== EVENT LISTENERS SETUP =====
// Attach event listeners
function attachEventListeners() {
  const loginForm = document.getElementById('login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', handleLogin);
  }

  const registerForm = document.getElementById('register-form');
  if (registerForm) {
    registerForm.addEventListener('submit', handleRegister);
  }

  const logoutBtn = document.querySelector('.logout-btn');
  if (logoutBtn) {
    logoutBtn.addEventListener('click', handleLogout);
  }

  const bookForm = document.getElementById('book-form');
  if (bookForm) {
    bookForm.addEventListener('submit', handleBookSubmit);
  }

  const modalClose = document.querySelector('.modal-close');
  if (modalClose) {
    modalClose.addEventListener('click', closeBookModal);
  }

  const modal = document.getElementById('book-modal');
  if (modal) {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeBookModal();
      }
    });
  }

  const menuToggle = document.querySelector('.menu-toggle');
  if (menuToggle) {
    menuToggle.addEventListener('click', () => {
      const navLinks = document.querySelector('.nav-links');
      navLinks.classList.toggle('active');
    });
  }

  const searchBookBtn = document.getElementById("search-book-btn");

if (searchBookBtn) {
    searchBookBtn.addEventListener("click", searchBook);
}
}

async function searchBook() {
    const title = document.getElementById("book-title").value.trim();

    if (!title) {
        alert("Please enter a book title first.");
        return;
    }

    try {
        const response = await fetch(
            `${API_BASE_URL}/books/search/${encodeURIComponent(title)}`,
            {
                headers: {
                    Authorization: `Bearer ${appState.token}`
                }
            }
        );

        const data = await response.json();

        document.getElementById("book-author").value = data.author || "";
        document.getElementById("book-genre").value = data.genre || "";
        document.getElementById("book-pages").value = data.pages || "";
        document.getElementById("book-review").value = data.description || "";

        if (data.isFallback) {
            alert("Book details loaded from local offline library cache!");
        } else {
            alert("Book information successfully loaded from Google Books!");
        }

    } catch (err) {
        console.error(err);
        alert("Unable to retrieve book information. Please fill in the fields manually.");
    }
}

// ===== UTILITY FUNCTIONS =====
// Format date
function formatDate(dateString) {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

// Escape HTML
function escapeHTML(text) {
  const map = {
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;'
  };
  return text.replace(/[&<>"']/g, m => map[m]);
}

console.log('JavaScript loaded successfully');
