// Get the genre dropdown
const genreFilter = document.getElementById('genreFilter');

// When dropdown changes
genreFilter.addEventListener('change', function() {
    // Get selected value
    const selected = this.value;
    
    // Get all cards
    const cards = document.querySelectorAll('.movie-card, .series-card');
    
    // Loop through cards
    for(let card of cards) {
        // Show/hide based on selection
        if(selected === 'all' || card.dataset.genre === selected) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    }
});

// Loading screen
window.addEventListener('load', function() {
    setTimeout(function() {
        document.querySelector('.loading-screen').style.opacity = '0';
        setTimeout(function() {
            document.querySelector('.loading-screen').style.display = 'none';
        }, 500);
    }, 1000);
});