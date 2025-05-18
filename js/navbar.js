
  /*  Toggle mobile menu */
  const mobileToggle = document.querySelector('.mobile-toggle');
  const headerMenu = document.querySelector('.header-menu');
  
  mobileToggle.addEventListener('click', function() {
    const isExpanded = this.getAttribute('aria-expanded') === 'true';
    this.setAttribute('aria-expanded', !isExpanded);
    this.classList.toggle('active');
    headerMenu.classList.toggle('active');
    
    /*  Close categories when closing mobile menu */
    if (isExpanded) {
      const activeCategories = document.querySelector('.categories.active');
      if (activeCategories) {
        activeCategories.classList.remove('active');
      }
    }
  });
  
  /* Categories functionality */
  const categoriesItem = document.querySelector('.categories');
  
  /* Desktop hover behavior */
  let hoverTimeout;

  categoriesItem.addEventListener('mouseenter', function () {
    if (window.innerWidth > 768) {
      clearTimeout(hoverTimeout);
      this.classList.add('hover-active');
    }
  });

  categoriesItem.addEventListener('mouseleave', function () {
    if (window.innerWidth > 768) {
      hoverTimeout = setTimeout(() => {
        this.classList.remove('hover-active');
      }, 200); /* delay in ms */
    }
  });

  /* Mobile click behavior */
  categoriesItem.addEventListener('click', function(e) {
    if (window.innerWidth <= 768) {
      e.stopPropagation();
      this.classList.toggle('active');
    }
  });
  
  /* Close categories when clicking outside */
  document.addEventListener('click', function(e) {
    if (!categoriesItem.contains(e.target) && window.innerWidth <= 768) {
      categoriesItem.classList.remove('active');
    }
  });
   
  /* Profile dropdown functionality */
  const profileContainer = document.querySelector('.profile-container');
  
  if (profileContainer) {
    profileContainer.addEventListener('click', function(e) {
      e.stopPropagation();
      this.classList.toggle('active');
    });
  }
  
  /* Close dropdowns when clicking outside */
  document.addEventListener('click', function() {
    if (profileContainer) profileContainer.classList.remove('active');
  });
  
  /* Search functionality */
  const searchInput = document.getElementById('searchInput');
  const searchResults = document.getElementById('searchResults');
  const mobileSearchInput = document.getElementById('mobileSearchInput');
  const mobileSearchResults = document.getElementById('mobileSearchResults');
  const mobileSearchBtn = document.getElementById('mobileSearchBtn');
  const mobileSearchOverlay = document.getElementById('mobileSearchOverlay');
  const mobileSearchClose = document.getElementById('mobileSearchClose');
  let searchTimeout;

  /* Mobile search button click */
  mobileSearchBtn.addEventListener('click', function() {
    mobileSearchOverlay.style.display = 'flex';
    mobileSearchInput.focus();
  });
  
  /* Mobile search close */
  mobileSearchClose.addEventListener('click', function() {
    mobileSearchOverlay.style.display = 'none';
  });

  /* Function to perform the search */
  async function performSearch(query, isMobile = false) {
    if (query.length < 2) {
      clearResults(isMobile);
      return;
    }
    
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(async () => {
      try {
        /* Make AJAX call to your PHP backend */
        const response = await fetch(`search.php?q=${encodeURIComponent(query)}`);
        
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        
        const results = await response.json();
        displayResults(results, isMobile);
      } catch (error) {
        console.error('Error fetching search results:', error);
        displayError(isMobile);
      }
    }, 300);
  }

  /* Function to clear search results */
  function clearResults(isMobile) {
    if (isMobile) {
      mobileSearchResults.innerHTML = '';
      mobileSearchResults.style.display = 'none';
    } else {
      searchResults.innerHTML = '';
      searchResults.style.display = 'none';
    }
  }

  function displayResults(results, isMobile) {
    const resultsContainer = isMobile ? mobileSearchResults : searchResults;
    
    if (!results || results.length === 0) {
        resultsContainer.innerHTML = '<div class="no-results">No results found</div>';
        resultsContainer.style.display = 'block';
        return;
    }
    
    let html = '';
    results.forEach(item => {
        /* Determine the correct link based on content type */
        const link = item.type === 'film' ? 
            `watch.php?movie=${item.id}` : 
            `serie.php?serie=${item.id}`;
        
        /* Format the date for display */
        const releaseDate = new Date(item.date_publication);
        const year = releaseDate.getFullYear();
        
        html += `
            <div class="search-result-item" onclick="window.location.href='${link}'">
                <img src="${item.image}" class="search-result-poster" alt="${item.titre}" 
                     onerror="this.src='/images/posters/default.jpg'">
                <div class="search-result-info">
                    <div class="search-result-title">${item.titre}</div>
                    <div class="search-result-year">${year} • <span class="search-result-type">${item.type === 'film' ? 'Movie' : 'TV Show'}</span></div>
                </div>
            </div>
        `;
    });
    
    resultsContainer.innerHTML = html;
    resultsContainer.style.display = 'block';
}

  /* Function to display error message */
  function displayError(isMobile) {
    const resultsContainer = isMobile ? mobileSearchResults : searchResults;
    resultsContainer.innerHTML = '<div class="no-results">Error loading results. Please try again.</div>';
    resultsContainer.style.display = 'block';
  }

  /* Event listeners for search input */
  searchInput.addEventListener('input', function() {
    performSearch(this.value.trim());
  });
  
  mobileSearchInput.addEventListener('input', function() {
    performSearch(this.value.trim(), true);
  });

  /* Close search results when clicking outside */
  document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
      searchResults.style.display = 'none';
    }
    
    if (!mobileSearchInput.contains(e.target) && !mobileSearchResults.contains(e.target) && 
        e.target !== mobileSearchBtn && !mobileSearchBtn.contains(e.target)) {
      if (!e.target.closest('.search-result-item')) {
        mobileSearchResults.style.display = 'none';
      }
    }
  });

  /* Keep results visible when hovering */
  searchResults.addEventListener('mouseenter', function() {
    this.style.display = 'block';
  });
  
  mobileSearchResults.addEventListener('mouseenter', function() {
    this.style.display = 'block';
  });