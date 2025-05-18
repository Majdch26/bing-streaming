// Change season tabs
function changeSeason(seasonNum) {
    document.querySelectorAll('.season-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.season-content').forEach(content => {
        content.style.display = 'none';
    });
    
    // Find the clicked season tab (now works with any number of seasons)
    const seasonTabs = document.querySelectorAll('.season-tab');
    for (let i = 0; i < seasonTabs.length; i++) {
        if (seasonTabs[i].textContent.includes(`Season ${seasonNum}`)) {
            seasonTabs[i].classList.add('active');
            break;
        }
    }
    
    document.getElementById(`season-${seasonNum}`).style.display = 'block';
}

// Loading screen
window.addEventListener('load', function() {
    setTimeout(function() {
        document.querySelector('.loading-screen').style.opacity = '0';
        setTimeout(function() {
            document.querySelector('.loading-screen').style.display = 'none';
        }, 500);
    }, 1000);
});