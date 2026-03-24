<?php
// student/course_content.php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth.php';

// Check if user is logged in and is a student
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: /skillacademy/login.php");
    exit();
}

$db = getDB();
$student_id = $_SESSION['user_id'];
$course_id = $_GET['id'] ?? 0;

// Verify student is enrolled in this course
$check_query = "SELECT * FROM enrollments 
                WHERE student_id = :student_id AND course_id = :course_id";
$check_stmt = $db->prepare($check_query);
$check_stmt->bindParam(':student_id', $student_id);
$check_stmt->bindParam(':course_id', $course_id);
$check_stmt->execute();

if($check_stmt->rowCount() == 0) {
    header("Location: my_courses.php?error=Not enrolled in this course");
    exit();
}

// Get course details
$course_query = "SELECT c.*, u.full_name as instructor_name, cat.name as category_name
                FROM courses c
                JOIN users u ON c.instructor_id = u.id
                JOIN categories cat ON c.category_id = cat.id
                WHERE c.id = :course_id";
$course_stmt = $db->prepare($course_query);
$course_stmt->bindParam(':course_id', $course_id);
$course_stmt->execute();
$course = $course_stmt->fetch(PDO::FETCH_ASSOC);

// Get enrollment details
$enroll_query = "SELECT * FROM enrollments 
                 WHERE student_id = :student_id AND course_id = :course_id";
$enroll_stmt = $db->prepare($enroll_query);
$enroll_stmt->bindParam(':student_id', $student_id);
$enroll_stmt->bindParam(':course_id', $course_id);
$enroll_stmt->execute();
$enrollment = $enroll_stmt->fetch(PDO::FETCH_ASSOC);

// Get completed lessons from session (or initialize if not exists)
$completed_key = 'completed_lessons_' . $course_id;
if(!isset($_SESSION[$completed_key])) {
    $_SESSION[$completed_key] = [];
}
$completed_lessons = $_SESSION[$completed_key];

// Calculate progress
$total_lessons = 5;
$completed_count = count($completed_lessons);
$progress_percentage = ($completed_count / $total_lessons) * 100;

// Define working videos for each lesson (using reliable educational content)
$lesson_videos = [
    1 => 'https://www.youtube.com/embed/UB1O30fR-EE',  // HTML Crash Course
    2 => 'https://www.youtube.com/embed/pQN-pnXPaVg',  // HTML Full Course
    3 => 'https://www.youtube.com/embed/1Rs2ND1ryYc',  // CSS Crash Course
    4 => 'https://www.youtube.com/embed/PkZNo7MFNFg',  // JavaScript Tutorial
    5 => 'https://www.youtube.com/embed/3JluqTojuME'   // Build a Website Tutorial
];

// Define lesson titles and content
$lesson_content = [
    1 => [
        'title' => 'Getting Started with Web Development',
        'content' => 'Welcome to the course! In this lesson, we will learn about the basics of web development, including how websites work, frontend vs backend, and setting up your development environment.',
        'points' => [
            'What is web development?',
            'How websites work',
            'Frontend vs Backend',
            'Setting up VS Code',
            'Your first HTML file'
        ],
        'duration' => 15
    ],
    2 => [
        'title' => 'HTML Fundamentals',
        'content' => 'Learn HTML tags, elements, attributes, and how to structure a webpage. We will cover headings, paragraphs, links, images, lists, and forms.',
        'points' => [
            'HTML document structure',
            'Common HTML tags',
            'Attributes and values',
            'Creating forms',
            'Semantic HTML'
        ],
        'duration' => 20
    ],
    3 => [
        'title' => 'CSS Styling Mastery',
        'content' => 'Master CSS styling including colors, fonts, layouts, flexbox, grid, and responsive design. Learn how to make your websites look professional and beautiful.',
        'points' => [
            'CSS selectors and properties',
            'Box model and layout',
            'Flexbox and Grid',
            'Responsive design',
            'Animations and transitions'
        ],
        'duration' => 25
    ],
    4 => [
        'title' => 'JavaScript Programming',
        'content' => 'Learn JavaScript fundamentals including variables, functions, loops, arrays, objects, and DOM manipulation. Make your websites interactive and dynamic.',
        'points' => [
            'Variables and data types',
            'Functions and scope',
            'DOM manipulation',
            'Event handling',
            'API integration'
        ],
        'duration' => 30
    ],
    5 => [
        'title' => 'Building a Complete Project',
        'content' => 'Apply everything you learned to build a complete responsive website. We will create a portfolio website with HTML, CSS, and JavaScript.',
        'points' => [
            'Responsive navigation',
            'Hero section',
            'About section',
            'Projects gallery',
            'Contact form'
        ],
        'duration' => 45
    ]
];

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar with course content -->
        <div class="col-md-3 col-lg-2 px-0 bg-light">
            <div class="sidebar p-3">
                <h5 class="mb-3"><?php echo htmlspecialchars($course['title']); ?></h5>
                <div class="list-group">
                    <a href="#overview" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-info-circle"></i> Course Overview
                    </a>
                    
                    <!-- Module 1 -->
                    <div class="list-group-item bg-light fw-bold">
                        Module 1: Introduction
                    </div>
                    <a href="#lesson1" class="list-group-item list-group-item-action ps-4" data-bs-toggle="list" id="lesson1-link">
                        <i class="fas fa-play-circle <?php echo in_array(1, $completed_lessons) ? 'text-success' : 'text-secondary'; ?>"></i> 
                        Lesson 1: <?php echo $lesson_content[1]['title']; ?>
                        <?php if(in_array(1, $completed_lessons)): ?>
                            <i class="fas fa-check-circle text-success float-end"></i>
                        <?php endif; ?>
                    </a>
                    <a href="#lesson2" class="list-group-item list-group-item-action ps-4" data-bs-toggle="list" id="lesson2-link">
                        <i class="fas fa-play-circle <?php echo in_array(2, $completed_lessons) ? 'text-success' : 'text-secondary'; ?>"></i> 
                        Lesson 2: <?php echo $lesson_content[2]['title']; ?>
                        <?php if(in_array(2, $completed_lessons)): ?>
                            <i class="fas fa-check-circle text-success float-end"></i>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Module 2 -->
                    <div class="list-group-item bg-light fw-bold">
                        Module 2: Core Technologies
                    </div>
                    <a href="#lesson3" class="list-group-item list-group-item-action ps-4" data-bs-toggle="list" id="lesson3-link">
                        <i class="fas fa-play-circle <?php echo in_array(3, $completed_lessons) ? 'text-success' : 'text-secondary'; ?>"></i> 
                        Lesson 3: <?php echo $lesson_content[3]['title']; ?>
                        <?php if(in_array(3, $completed_lessons)): ?>
                            <i class="fas fa-check-circle text-success float-end"></i>
                        <?php endif; ?>
                    </a>
                    <a href="#lesson4" class="list-group-item list-group-item-action ps-4" data-bs-toggle="list" id="lesson4-link">
                        <i class="fas fa-play-circle <?php echo in_array(4, $completed_lessons) ? 'text-success' : 'text-secondary'; ?>"></i> 
                        Lesson 4: <?php echo $lesson_content[4]['title']; ?>
                        <?php if(in_array(4, $completed_lessons)): ?>
                            <i class="fas fa-check-circle text-success float-end"></i>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Module 3 -->
                    <div class="list-group-item bg-light fw-bold">
                        Module 3: Projects
                    </div>
                    <a href="#lesson5" class="list-group-item list-group-item-action ps-4" data-bs-toggle="list" id="lesson5-link">
                        <i class="fas fa-play-circle <?php echo in_array(5, $completed_lessons) ? 'text-success' : 'text-secondary'; ?>"></i> 
                        Lesson 5: <?php echo $lesson_content[5]['title']; ?>
                        <?php if(in_array(5, $completed_lessons)): ?>
                            <i class="fas fa-check-circle text-success float-end"></i>
                        <?php endif; ?>
                    </a>
                    
                    <!-- Quiz/Assessment -->
                    <a href="#quiz" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-question-circle"></i> Final Quiz
                    </a>
                </div>
                
                <!-- Progress Section -->
                <div class="mt-4 pt-3 border-top">
                    <label class="fw-bold">Your Progress</label>
                    <div class="progress mt-2" style="height: 25px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             id="progress-bar"
                             style="width: <?php echo $progress_percentage; ?>%"
                             aria-valuenow="<?php echo $progress_percentage; ?>" 
                             aria-valuemin="0" aria-valuemax="100">
                            <span id="progress-text"><?php echo round($progress_percentage); ?>%</span>
                        </div>
                    </div>
                    <small class="text-muted d-block mt-2">
                        <i class="fas fa-check-circle text-success"></i> 
                        <span id="completed-count"><?php echo $completed_count; ?></span> of <?php echo $total_lessons; ?> lessons completed
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Main Content Area -->
        <div class="col-md-9 col-lg-10 py-4">
            <div class="container">
                <div class="tab-content">
                    <!-- Overview Tab -->
                    <div class="tab-pane active" id="overview">
                        <h2><?php echo htmlspecialchars($course['title']); ?></h2>
                        <p class="text-muted">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($course['instructor_name']); ?> |
                            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($course['category_name']); ?> |
                            <i class="fas fa-signal"></i> Level: <?php echo ucfirst($course['level']); ?> |
                            <i class="fas fa-clock"></i> Duration: <?php echo floor($course['duration'] / 60); ?> hours
                        </p>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Course Description</h5>
                            </div>
                            <div class="card-body">
                                <p><?php echo nl2br(htmlspecialchars($course['description'])); ?></p>
                            </div>
                        </div>
                        
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">What You'll Learn</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success"></i> Master HTML, CSS, and JavaScript</li>
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success"></i> Build responsive websites</li>
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success"></i> Create interactive web applications</li>
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success"></i> Build real-world projects</li>
                                    <li class="list-group-item"><i class="fas fa-check-circle text-success"></i> Get hands-on coding experience</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lesson 1: Getting Started -->
                    <div class="tab-pane" id="lesson1">
                        <h2>Lesson 1: <?php echo $lesson_content[1]['title']; ?></h2>
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="<?php echo $lesson_videos[1]; ?>" title="Lesson 1 video" allowfullscreen frameborder="0"></iframe>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5>Lesson Notes</h5>
                                <p><?php echo $lesson_content[1]['content']; ?></p>
                                <ul>
                                    <?php foreach($lesson_content[1]['points'] as $point): ?>
                                    <li><?php echo $point; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Duration:</strong> <?php echo $lesson_content[1]['duration']; ?> minutes</p>
                                
                                <?php if(in_array(1, $completed_lessons)): ?>
                                    <button class="btn btn-outline-success" disabled>
                                        <i class="fas fa-check"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success mark-complete" data-lesson="1">
                                        <i class="fas fa-check"></i> Mark as Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lesson 2: HTML Fundamentals -->
                    <div class="tab-pane" id="lesson2">
                        <h2>Lesson 2: <?php echo $lesson_content[2]['title']; ?></h2>
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="<?php echo $lesson_videos[2]; ?>" title="Lesson 2 video" allowfullscreen frameborder="0"></iframe>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5>Lesson Notes</h5>
                                <p><?php echo $lesson_content[2]['content']; ?></p>
                                <ul>
                                    <?php foreach($lesson_content[2]['points'] as $point): ?>
                                    <li><?php echo $point; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Duration:</strong> <?php echo $lesson_content[2]['duration']; ?> minutes</p>
                                
                                <?php if(in_array(2, $completed_lessons)): ?>
                                    <button class="btn btn-outline-success" disabled>
                                        <i class="fas fa-check"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success mark-complete" data-lesson="2">
                                        <i class="fas fa-check"></i> Mark as Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lesson 3: CSS Styling -->
                    <div class="tab-pane" id="lesson3">
                        <h2>Lesson 3: <?php echo $lesson_content[3]['title']; ?></h2>
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="<?php echo $lesson_videos[3]; ?>" title="Lesson 3 video" allowfullscreen frameborder="0"></iframe>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5>Lesson Notes</h5>
                                <p><?php echo $lesson_content[3]['content']; ?></p>
                                <ul>
                                    <?php foreach($lesson_content[3]['points'] as $point): ?>
                                    <li><?php echo $point; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Duration:</strong> <?php echo $lesson_content[3]['duration']; ?> minutes</p>
                                
                                <?php if(in_array(3, $completed_lessons)): ?>
                                    <button class="btn btn-outline-success" disabled>
                                        <i class="fas fa-check"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success mark-complete" data-lesson="3">
                                        <i class="fas fa-check"></i> Mark as Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lesson 4: JavaScript -->
                    <div class="tab-pane" id="lesson4">
                        <h2>Lesson 4: <?php echo $lesson_content[4]['title']; ?></h2>
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="<?php echo $lesson_videos[4]; ?>" title="Lesson 4 video" allowfullscreen frameborder="0"></iframe>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5>Lesson Notes</h5>
                                <p><?php echo $lesson_content[4]['content']; ?></p>
                                <ul>
                                    <?php foreach($lesson_content[4]['points'] as $point): ?>
                                    <li><?php echo $point; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Duration:</strong> <?php echo $lesson_content[4]['duration']; ?> minutes</p>
                                
                                <?php if(in_array(4, $completed_lessons)): ?>
                                    <button class="btn btn-outline-success" disabled>
                                        <i class="fas fa-check"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success mark-complete" data-lesson="4">
                                        <i class="fas fa-check"></i> Mark as Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Lesson 5: Project -->
                    <div class="tab-pane" id="lesson5">
                        <h2>Lesson 5: <?php echo $lesson_content[5]['title']; ?></h2>
                        <div class="ratio ratio-16x9 mb-4">
                            <iframe src="<?php echo $lesson_videos[5]; ?>" title="Lesson 5 video" allowfullscreen frameborder="0"></iframe>
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h5>Lesson Notes</h5>
                                <p><?php echo $lesson_content[5]['content']; ?></p>
                                <ul>
                                    <?php foreach($lesson_content[5]['points'] as $point): ?>
                                    <li><?php echo $point; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <p><strong>Duration:</strong> <?php echo $lesson_content[5]['duration']; ?> minutes</p>
                                
                                <?php if(in_array(5, $completed_lessons)): ?>
                                    <button class="btn btn-outline-success" disabled>
                                        <i class="fas fa-check"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-success mark-complete" data-lesson="5">
                                        <i class="fas fa-check"></i> Mark as Complete
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quiz Tab -->
                    <div class="tab-pane" id="quiz">
                        <h2>Final Quiz</h2>
                        <div class="card">
                            <div class="card-body">
                                <form id="quizForm">
                                    <div class="mb-4">
                                        <h5>Question 1: What does HTML stand for?</h5>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q1" value="a">
                                            <label class="form-check-label">Hyper Text Markup Language</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q1" value="b">
                                            <label class="form-check-label">High Tech Modern Language</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q1" value="c">
                                            <label class="form-check-label">Hyper Transfer Markup Language</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Question 2: What does CSS stand for?</h5>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q2" value="a">
                                            <label class="form-check-label">Cascading Style Sheets</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q2" value="b">
                                            <label class="form-check-label">Creative Style Sheets</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q2" value="c">
                                            <label class="form-check-label">Computer Style Sheets</label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <h5>Question 3: Which language is used for web interactivity?</h5>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q3" value="a">
                                            <label class="form-check-label">HTML</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q3" value="b">
                                            <label class="form-check-label">CSS</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="q3" value="c">
                                            <label class="form-check-label">JavaScript</label>
                                        </div>
                                    </div>
                                    
                                    <button type="button" class="btn btn-primary" onclick="submitQuiz()">
                                        Submit Quiz
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Track completed lessons
let completedLessons = <?php echo json_encode($completed_lessons); ?>;
const totalLessons = 5;
let completedCount = completedLessons.length;

// Update progress display function
function updateProgressDisplay() {
    const progress = (completedCount / totalLessons) * 100;
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const completedCountSpan = document.getElementById('completed-count');
    
    if(progressBar) {
        progressBar.style.width = progress + '%';
        progressBar.setAttribute('aria-valuenow', progress);
    }
    if(progressText) {
        progressText.textContent = Math.round(progress) + '%';
    }
    if(completedCountSpan) {
        completedCountSpan.textContent = completedCount;
    }
    
    if(completedCount === totalLessons) {
        setTimeout(() => {
            alert('🎉 Great job! You have completed all lessons! Take the final quiz to finish the course.');
        }, 500);
    }
}

// Mark lesson as complete
document.querySelectorAll('.mark-complete').forEach(button => {
    button.addEventListener('click', function() {
        const lessonId = parseInt(this.dataset.lesson);
        
        if(completedLessons.includes(lessonId)) {
            return;
        }
        
        completedLessons.push(lessonId);
        completedCount++;
        
        fetch('save_progress.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `course_id=<?php echo $course_id; ?>&lesson_id=${lessonId}&action=complete`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                this.innerHTML = '<i class="fas fa-check"></i> Completed';
                this.classList.remove('btn-success');
                this.classList.add('btn-outline-success');
                this.disabled = true;
                
                const lessonLink = document.getElementById(`lesson${lessonId}-link`);
                if(lessonLink) {
                    const icon = lessonLink.querySelector('i:first-child');
                    if(icon) {
                        icon.classList.remove('text-secondary');
                        icon.classList.add('text-success');
                    }
                    if(!lessonLink.querySelector('.fa-check-circle')) {
                        const checkmark = document.createElement('i');
                        checkmark.className = 'fas fa-check-circle text-success float-end';
                        lessonLink.appendChild(checkmark);
                    }
                }
                
                updateProgressDisplay();
                alert(`Lesson ${lessonId} marked as complete!`);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error saving progress. Please try again.');
        });
    });
});

// Submit quiz
function submitQuiz() {
    if(completedCount < totalLessons) {
        alert(`Please complete all lessons before taking the quiz. You have completed ${completedCount} out of ${totalLessons} lessons.`);
        return;
    }
    
    const q1 = document.querySelector('input[name="q1"]:checked');
    const q2 = document.querySelector('input[name="q2"]:checked');
    const q3 = document.querySelector('input[name="q3"]:checked');
    
    if(!q1 || !q2 || !q3) {
        alert('Please answer all questions before submitting.');
        return;
    }
    
    let score = 0;
    if(q1.value === 'a') score++;
    if(q2.value === 'a') score++;
    if(q3.value === 'c') score++;
    
    const percentage = (score / 3) * 100;
    
    if(percentage >= 70) {
        // Show loading indicator
        const submitBtn = document.querySelector('#quizForm button');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        submitBtn.disabled = true;
        
        // Mark course as completed in database
        fetch('complete_course.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `course_id=<?php echo $course_id; ?>`
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                alert(`🎉 Congratulations! You scored ${score}/3 (${percentage}%)\n\nYou have successfully completed the course!`);
                window.location.href = 'my_courses.php';
            } else {
                alert('Error completing course. Please try again.');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error completing course. Please try again.');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    } else {
        alert(`You scored ${score}/3 (${percentage}%)\n\nPlease review the course material and try again.`);
    }
}

// Initialize progress display on page load
updateProgressDisplay();
</script>

<style>
.tab-pane {
    padding: 20px;
}
.list-group-item.active {
    background-color: #007bff;
    border-color: #007bff;
}
.ratio {
    background: #000;
    border-radius: 8px;
    overflow: hidden;
}
.ratio iframe {
    border: none;
}
.mark-complete {
    margin-top: 15px;
}
.progress {
    border-radius: 20px;
    overflow: hidden;
}
.progress-bar {
    transition: width 0.5s ease;
}
</style>

<?php include '../includes/footer.php'; ?>

