// assets/js/script.js

// Global variables
const API_BASE_URL = '/skillacademy';
let currentUser = null;

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    console.log('Skill Academy JS loaded');
    
    // Initialize all components
    initTooltips();
    initAutoHideAlerts();
    initConfirmDialogs();
    initCourseSearch();
    initCategoryFilters();
    initFormValidation();
    initPasswordToggle();
    initCourseProgress();
    initMobileMenu();
    initInstructorForms();
    initRatingSystem();
    
    // Check if user is logged in (from session)
    checkUserStatus();
});

// ==================== UTILITY FUNCTIONS ====================

// Check if user is logged in (PHP session will handle this, but JS can check for user data in DOM)
function checkUserStatus() {
    const userMenu = document.getElementById('userMenu');
    if (userMenu) {
        // User is logged in (menu exists)
        currentUser = {
            name: userMenu.dataset.userName || 'User',
            role: userMenu.dataset.userRole || 'student'
        };
    }
}

// Show loading spinner
function showSpinner() {
    const spinner = document.createElement('div');
    spinner.className = 'spinner-overlay';
    spinner.id = 'globalSpinner';
    spinner.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    document.body.appendChild(spinner);
}

// Hide loading spinner
function hideSpinner() {
    const spinner = document.getElementById('globalSpinner');
    if (spinner) {
        spinner.remove();
    }
}

// Show toast notification
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    // Remove toast after it's hidden
    toast.addEventListener('hidden.bs.toast', function() {
        this.remove();
    });
}

// Create toast container if it doesn't exist
function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// ==================== INITIALIZATION FUNCTIONS ====================

// Initialize Bootstrap tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Auto-hide alerts after 5 seconds
function initAutoHideAlerts() {
    setTimeout(function() {
        document.querySelectorAll('.alert:not(.alert-permanent)').forEach(function(alert) {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(function() {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        });
    }, 5000);
}

// Confirm dialogs for delete actions
function initConfirmDialogs() {
    document.querySelectorAll('.confirm-delete, .delete-btn, [data-confirm]').forEach(function(element) {
        element.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure you want to delete this item?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

// Course search functionality
function initCourseSearch() {
    const searchInput = document.getElementById('searchCourses');
    if (!searchInput) return;
    
    let searchTimeout;
    
    searchInput.addEventListener('keyup', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            const searchTerm = this.value.toLowerCase().trim();
            const courseCards = document.querySelectorAll('.course-card');
            
            if (searchTerm === '') {
                courseCards.forEach(card => card.style.display = 'block');
                return;
            }
            
            courseCards.forEach(card => {
                const title = card.querySelector('.card-title')?.textContent.toLowerCase() || '';
                const instructor = card.querySelector('.instructor')?.textContent.toLowerCase() || '';
                const description = card.querySelector('.card-text')?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || instructor.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                    // Add highlight effect
                    card.style.animation = 'fadeIn 0.5s';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show no results message
            const visibleCount = Array.from(courseCards).filter(card => card.style.display !== 'none').length;
            updateNoResultsMessage(visibleCount === 0);
        }, 300);
    });
}

// Show/hide no results message
function updateNoResultsMessage(show) {
    let noResultsMsg = document.getElementById('noResultsMessage');
    
    if (show) {
        if (!noResultsMsg) {
            noResultsMsg = document.createElement('div');
            noResultsMsg.id = 'noResultsMessage';
            noResultsMsg.className = 'alert alert-info text-center mt-4';
            noResultsMsg.innerHTML = '<i class="fas fa-search"></i> No courses found matching your search.';
            
            const courseGrid = document.querySelector('.row.g-4');
            if (courseGrid) {
                courseGrid.parentNode.insertBefore(noResultsMsg, courseGrid);
            }
        }
    } else if (noResultsMsg) {
        noResultsMsg.remove();
    }
}

// Category filters
function initCategoryFilters() {
    const categoryFilters = document.querySelectorAll('.category-filter');
    if (categoryFilters.length === 0) return;
    
    categoryFilters.forEach(filter => {
        filter.addEventListener('change', function() {
            const category = this.value;
            const url = new URL(window.location.href);
            
            if (category) {
                url.searchParams.set('category', category);
            } else {
                url.searchParams.delete('category');
            }
            
            window.location.href = url.toString();
        });
    });
}

// Form validation
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
                
                // Scroll to first error
                const firstInvalid = form.querySelector(':invalid');
                if (firstInvalid) {
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstInvalid.focus();
                }
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password confirmation validation
    const passwordField = document.getElementById('password');
    const confirmPasswordField = document.getElementById('confirm_password');
    
    if (passwordField && confirmPasswordField) {
        function validatePasswordMatch() {
            if (passwordField.value !== confirmPasswordField.value) {
                confirmPasswordField.setCustomValidity('Passwords do not match');
                confirmPasswordField.classList.add('is-invalid');
                
                let errorDiv = confirmPasswordField.nextElementSibling;
                if (!errorDiv || !errorDiv.classList.contains('invalid-feedback')) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = 'Passwords do not match';
                    confirmPasswordField.parentNode.appendChild(errorDiv);
                }
            } else {
                confirmPasswordField.setCustomValidity('');
                confirmPasswordField.classList.remove('is-invalid');
            }
        }
        
        passwordField.addEventListener('keyup', validatePasswordMatch);
        confirmPasswordField.addEventListener('keyup', validatePasswordMatch);
    }
}

// Password visibility toggle
function initPasswordToggle() {
    document.querySelectorAll('.toggle-password').forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const passwordInput = document.getElementById(targetId);
            
            if (passwordInput) {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                
                // Toggle icon
                const icon = this.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                }
            }
        });
    });
}

// Course progress tracking
function initCourseProgress() {
    const progressBars = document.querySelectorAll('.course-progress');
    
    progressBars.forEach(bar => {
        const progress = parseFloat(bar.dataset.progress) || 0;
        const progressBar = bar.querySelector('.progress-bar');
        
        if (progressBar) {
            progressBar.style.width = progress + '%';
            progressBar.setAttribute('aria-valuenow', progress);
            progressBar.textContent = progress + '%';
        }
    });
    
    // Mark lessons as complete
    document.querySelectorAll('.mark-complete').forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const lessonId = this.dataset.lessonId;
            const courseId = this.dataset.courseId;
            
            if (this.checked) {
                updateLessonProgress(lessonId, courseId, 'complete');
            } else {
                updateLessonProgress(lessonId, courseId, 'incomplete');
            }
        });
    });
}

// Update lesson progress via AJAX
function updateLessonProgress(lessonId, courseId, action) {
    showSpinner();
    
    fetch(`${API_BASE_URL}/ajax/update_progress.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `lesson_id=${lessonId}&course_id=${courseId}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        hideSpinner();
        if (data.success) {
            updateOverallProgress(data.overall_progress);
            showToast('Progress updated successfully', 'success');
        } else {
            showToast('Error updating progress', 'danger');
        }
    })
    .catch(error => {
        hideSpinner();
        console.error('Error:', error);
        showToast('Error updating progress', 'danger');
    });
}

// Update overall course progress
function updateOverallProgress(progress) {
    const overallBar = document.querySelector('.overall-progress .progress-bar');
    if (overallBar) {
        overallBar.style.width = progress + '%';
        overallBar.setAttribute('aria-valuenow', progress);
        overallBar.textContent = progress + '%';
    }
}

// Mobile menu improvements
function initMobileMenu() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarToggler && navbarCollapse) {
        // Close menu when clicking outside
        document.addEventListener('click', function(event) {
            if (!navbarCollapse.contains(event.target) && !navbarToggler.contains(event.target)) {
                if (navbarCollapse.classList.contains('show')) {
                    navbarToggler.click();
                }
            }
        });
        
        // Close menu when clicking a nav link
        navbarCollapse.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', () => {
                if (navbarCollapse.classList.contains('show')) {
                    navbarToggler.click();
                }
            });
        });
    }
}

// Instructor course management forms
function initInstructorForms() {
    // Dynamic module addition
    const addModuleBtn = document.getElementById('addModule');
    if (addModuleBtn) {
        let moduleCount = document.querySelectorAll('.module-item').length;
        
        addModuleBtn.addEventListener('click', function() {
            moduleCount++;
            const moduleHtml = createModuleHtml(moduleCount);
            document.getElementById('modulesContainer').insertAdjacentHTML('beforeend', moduleHtml);
        });
    }
    
    // Dynamic lesson addition
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-lesson') || e.target.closest('.add-lesson')) {
            const button = e.target.classList.contains('add-lesson') ? e.target : e.target.closest('.add-lesson');
            const moduleId = button.dataset.moduleId;
            const lessonCount = document.querySelectorAll(`.lesson-item[data-module="${moduleId}"]`).length;
            
            const lessonHtml = createLessonHtml(moduleId, lessonCount + 1);
            document.getElementById(`lessons-${moduleId}`).insertAdjacentHTML('beforeend', lessonHtml);
        }
    });
    
    // Remove item
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-item') || e.target.closest('.remove-item')) {
            const button = e.target.classList.contains('remove-item') ? e.target : e.target.closest('.remove-item');
            const item = button.closest('.module-item, .lesson-item');
            
            if (item && confirm('Are you sure you want to remove this item?')) {
                item.remove();
            }
        }
    });
}

// Create module HTML for dynamic forms
function createModuleHtml(index) {
    return `
        <div class="module-item card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Module ${index}</h5>
                <button type="button" class="btn btn-sm btn-danger remove-item">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Module Title</label>
                    <input type="text" class="form-control" name="modules[${index}][title]" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea class="form-control" name="modules[${index}][description]" rows="2"></textarea>
                </div>
                <div class="lessons-container" id="lessons-${index}">
                    ${createLessonHtml(index, 1)}
                </div>
                <button type="button" class="btn btn-sm btn-primary add-lesson" data-module-id="${index}">
                    <i class="fas fa-plus"></i> Add Lesson
                </button>
            </div>
        </div>
    `;
}

// Create lesson HTML for dynamic forms
function createLessonHtml(moduleId, lessonIndex) {
    return `
        <div class="lesson-item card mb-2" data-module="${moduleId}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="mb-0">Lesson ${lessonIndex}</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-item">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-2">
                        <input type="text" class="form-control" name="modules[${moduleId}][lessons][${lessonIndex}][title]" placeholder="Lesson Title" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <input type="text" class="form-control" name="modules[${moduleId}][lessons][${lessonIndex}][duration]" placeholder="Duration (mins)">
                    </div>
                    <div class="col-md-2 mb-2">
                        <select class="form-select" name="modules[${moduleId}][lessons][${lessonIndex}][type]">
                            <option value="video">Video</option>
                            <option value="text">Text</option>
                            <option value="quiz">Quiz</option>
                        </select>
                    </div>
                </div>
                <div class="mb-2">
                    <textarea class="form-control" name="modules[${moduleId}][lessons][${lessonIndex}][content]" placeholder="Lesson content or video URL" rows="2"></textarea>
                </div>
            </div>
        </div>
    `;
}

// Rating system
function initRatingSystem() {
    const ratingStars = document.querySelectorAll('.rating-star');
    
    ratingStars.forEach(star => {
        star.addEventListener('mouseenter', function() {
            const rating = parseInt(this.dataset.rating);
            highlightStars(rating);
        });
        
        star.addEventListener('mouseleave', function() {
            const currentRating = document.getElementById('currentRating')?.value || 0;
            highlightStars(currentRating);
        });
        
        star.addEventListener('click', function() {
            const rating = parseInt(this.dataset.rating);
            document.getElementById('currentRating').value = rating;
            highlightStars(rating);
            
            // Enable submit button
            document.getElementById('submitReview').disabled = false;
        });
    });
}

// Highlight stars based on rating
function highlightStars(rating) {
    document.querySelectorAll('.rating-star').forEach((star, index) => {
        if (index < rating) {
            star.classList.remove('far');
            star.classList.add('fas', 'text-warning');
        } else {
            star.classList.remove('fas', 'text-warning');
            star.classList.add('far');
        }
    });
}

// Submit review via AJAX
function submitReview(courseId) {
    const rating = document.getElementById('currentRating').value;
    const comment = document.getElementById('reviewComment').value;
    
    if (!rating || rating < 1) {
        showToast('Please select a rating', 'warning');
        return;
    }
    
    showSpinner();
    
    fetch(`${API_BASE_URL}/ajax/submit_review.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `course_id=${courseId}&rating=${rating}&comment=${encodeURIComponent(comment)}`
    })
    .then(response => response.json())
    .then(data => {
        hideSpinner();
        if (data.success) {
            showToast('Review submitted successfully!', 'success');
            setTimeout(() => {
                location.reload();
            }, 1500);
        } else {
            showToast('Error submitting review', 'danger');
        }
    })
    .catch(error => {
        hideSpinner();
        console.error('Error:', error);
        showToast('Error submitting review', 'danger');
    });
}

// Course enrollment
function enrollCourse(courseId) {
    if (!currentUser) {
        window.location.href = `${API_BASE_URL}/login.php?redirect=${encodeURIComponent(window.location.href)}`;
        return;
    }
    
    showSpinner();
    
    fetch(`${API_BASE_URL}/ajax/enroll.php`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `course_id=${courseId}`
    })
    .then(response => response.json())
    .then(data => {
        hideSpinner();
        if (data.success) {
            showToast('Successfully enrolled!', 'success');
            setTimeout(() => {
                window.location.href = `${API_BASE_URL}/student/my_courses.php`;
            }, 1500);
        } else {
            showToast(data.message || 'Error enrolling in course', 'danger');
        }
    })
    .catch(error => {
        hideSpinner();
        console.error('Error:', error);
        showToast('Error enrolling in course', 'danger');
    });
}

// Export functions for global use
window.showToast = showToast;
window.showSpinner = showSpinner;
window.hideSpinner = hideSpinner;
window.submitReview = submitReview;
window.enrollCourse = enrollCourse;