/* ===== VARIABLES GLOBALES ===== */
var isLoggedIn = false;
var currentUser = null;
var selectedCategory = "Todas";
var coursesData = []; // Variable para almacenar los cursos

/* ===== INICIALIZACION ===== */
document.addEventListener('DOMContentLoaded', function() {
    console.log('- Iniciando aplicación...');
    checkSessionStatus();
    init();

    // Conectar los formularios a las funciones de JS
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);
});

async function init() {
    try {
        await loadCoursesData();
        console.log('Total de cursos en catálogo:', coursesData.length);

        if (coursesData.length === 0) {
            console.warn('No hay cursos en la base de datos');
        }

        renderCourses();
        renderCategories();
        setupScrollListener();
    } catch (error) {
        console.error('Error en inicialización:', error);
    }
}

/* ===== FUNCIONES DE MODAL ===== */
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function switchToModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    openModal(toModalId);
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

/* ===== FUNCIONES DE AUTENTICACIÓN ===== */

// Cargar datos del curso desde el servidor
async function loadCoursesData() {
    try {
        const response = await fetch('api/get_courses.php');
        coursesData = await response.json();
        console.log('Cursos cargados en index:', coursesData.length, coursesData);
    } catch (error) {
        console.error('Error al cargar los datos de los cursos:', error);
        coursesData = [];
    }
}

async function handleRegister(event) {
    event.preventDefault();
    const form = document.getElementById('registerForm');
    const formData = new FormData(form);
    const password = formData.get('password');
    const confirmPassword = formData.get('confirmPassword');

    if (password !== confirmPassword) {
        alert('Las contraseñas no coinciden. Por favor, revísalas.');
        return;
    }

    try {
        const response = await fetch('api/register.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        alert(result.message);
        if (response.ok) {
            switchToModal('registerModal', 'loginModal');
            form.reset();
        }
    } catch (error) {
        alert('Ocurrió un error de conexión. Por favor, inténtalo de nuevo.');
        console.error('Error en el registro:', error);
    }
}

async function handleLogin(event) {
    event.preventDefault();
    const form = document.getElementById('loginForm');
    const formData = new FormData(form);

    try {
        const response = await fetch('api/login.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();

        if (response.ok) {
            isLoggedIn = true;
            currentUser = { name: result.name };
            updateUserUI();
            closeModal('loginModal');
            form.reset();
        } else {
            alert(result.message);
        }
    } catch (error) {
        alert('Ocurrió un error al iniciar sesión.');
        console.error('Error en el login:', error);
    }
}

async function checkSessionStatus() {
    try {
        const response = await fetch('api/check_session.php');
        if (response.ok) {
            const data = await response.json();
            if (data.loggedIn) {
                isLoggedIn = true;
                currentUser = { name: data.name };
                updateUserUI();
            }
        }
    } catch (error) {
        console.error('Error al verificar el estado de la sesión:', error);
    }
}

async function logout() {
    closeUserDropdown();
    try {
        await fetch('api/logout.php');
    } catch (error) {
        console.error('Error al cerrar la sesión en el servidor:', error);
    }
    isLoggedIn = false;
    currentUser = null;
    updateUserUI();
}

function updateUserUI() {
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const userSection = document.getElementById('userSection');
    const userName = document.getElementById('userName');

    if (isLoggedIn && currentUser) {
        loginBtn.style.display = 'none';
        registerBtn.style.display = 'none';
        userSection.classList.remove('hidden');
        userName.textContent = 'Hola, ' + currentUser.name;
    } else {
        loginBtn.style.display = 'block';
        registerBtn.style.display = 'block';
        userSection.classList.add('hidden');
    }
}

/* ===== FUNCIÓN PARA IR A MIS CURSOS ===== */
function goToMyCourses() {
    closeUserDropdown();
    console.log('goToMyCourses llamada');
    console.log('isLoggedIn:', isLoggedIn);

    if (!isLoggedIn) {
        alert('Debes iniciar sesión para ver tus cursos.');
        openModal('loginModal');
        return;
    }

    console.log('Redirigiendo a mis_cursos.html');
    window.location.href = 'pages/mis_cursos.html';
}

/* ===== OTRAS FUNCIONES (NAVEGACIÓN, CURSOS, ETC.) ===== */

function scrollToSection(sectionId) {
    document.getElementById(sectionId).scrollIntoView({ behavior: 'smooth' });
}

function setupScrollListener() {
    window.addEventListener('scroll', updateActiveNav);
}

function updateActiveNav() {
    const sections = ['inicio', 'nosotros', 'mision', 'vision', 'cursos', 'contacto'];
    const scrollPosition = window.scrollY + 100;
    sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        const link = document.querySelector(`a[onclick="scrollToSection('${sectionId}')"]`);
        if (section && link) {
            if (scrollPosition >= section.offsetTop && scrollPosition < section.offsetTop + section.offsetHeight) {
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                link.classList.add('active');
            }
        }
    });
}

function renderCategories() {
    const categories = ['Todas', ...new Set(coursesData.map(course => course.category))];
    const categoryFilters = document.getElementById('categoryFilters');
    categoryFilters.innerHTML = '';
    categories.forEach(category => {
        const btn = document.createElement('button');
        btn.className = 'category-btn';
        if (category === 'Todas') btn.classList.add('active');
        btn.textContent = category;
        btn.onclick = (e) => filterByCategory(category, e.target);
        categoryFilters.appendChild(btn);
    });
}

function filterByCategory(category, clickedButton) {
    selectedCategory = category;
    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
    clickedButton.classList.add('active');
    renderCourses();
}

function filterCourses() {
    renderCourses();
}

function renderCourses() {
    const grid = document.getElementById('coursesGrid');
    const searchValue = document.getElementById('searchCourses').value.toLowerCase();
    grid.innerHTML = '';

    const filtered = coursesData.filter(course => {
        const matchSearch = course.title.toLowerCase().includes(searchValue) || course.instructor.toLowerCase().includes(searchValue);
        const matchCategory = selectedCategory === 'Todas' || course.category === selectedCategory;
        return matchSearch && matchCategory;
    });

    console.log(`🔍 Filtro: "${selectedCategory}", Busca: "${searchValue}", Resultados: ${filtered.length}/${coursesData.length}`);

    if (filtered.length === 0) {
        grid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 4rem 0;"><h3>No se encontraron cursos</h3></div>';
        return;
    }
    filtered.forEach(course => grid.appendChild(createCourseCard(course)));
}

function createCourseCard(course) {
    const card = document.createElement('div');
    card.className = 'course-card';
    // PW-11: Mostrar "Curso Gratuito" si el precio es 0
    const priceDisplay = course.price === 0
        ? 'Curso Gratuito'
        : '$' + Number(course.price).toLocaleString('es-CL');


    card.innerHTML = `
        <img src="${course.image}" alt="${course.title}" class="course-image">
        <div class="course-body">
            <span class="course-category">${course.category}</span>
            <h3 class="course-title">${course.title}</h3>
            <div class="course-instructor">
                <img src="${course.avatar || 'assets/default-avatar.png'}" alt="${course.instructor}" class="instructor-avatar">
                <div class="instructor-info"><div class="instructor-name">${course.instructor}</div></div>
            </div>
            <div class="course-rating">
                <span class="rating-stars">⭐ ${course.rating || 5.0}</span>
                <span>👥 ${course.students || 0} estudiantes</span>
            </div>
            <p class="course-description">${course.description}</p>
            <div class="course-footer">
                <div class="course-price">${priceDisplay}</div>
                <div class="course-actions">
                    <a href="pages/curso.html?id=${course.id}" class="btn-course" style="text-decoration:none; display:inline-block; text-align:center;">
                        Comenzar →
                    </a>
                </div>
            </div>
        </div>`;
    return card;
}

async function enrollCourse(id) {
    if (!isLoggedIn) {
        alert('Debes iniciar sesión para inscribirte.');
        openModal('loginModal');
        return;
    }

    try {
        const formData = new FormData();
        formData.append('course_id', id);

        const response = await fetch('api/enroll_course.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (response.ok) {
            alert('¡Inscripción exitosa! Revisa tus cursos');
            // Redirigir a mis cursos después de 1.5 segundos
            setTimeout(() => {
                window.location.href = 'pages/mis_cursos.html';
            }, 1500);
        } else {
            alert(result.error || result.message || 'Error al inscribirse');
        }
    } catch (error) {
        alert('Error de conexión al inscribirse');
        console.error('Error:', error);
    }
}

function sendMessage(event) {
    event.preventDefault();
    event.target.reset();
    alert('¡Gracias por tu mensaje! Nos pondremos en contacto pronto.');
}

/* Menu desplegable */
function toggleUserDropdown() {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');

    btn.classList.toggle('active');
    menu.classList.toggle('show');
}

function closeUserDropdown() {
    const btn = document.getElementById('userDropdownBtn');
    const menu = document.getElementById('userDropdownMenu');

    btn.classList.remove('active');
    menu.classList.remove('show');
}

// Cerrar dropdown cuando se hace click fuera
document.addEventListener('click', function(event) {
    const userSection = document.getElementById('userSection');
    const userDropdown = document.querySelector('.user-dropdown');

    if (userSection && !userSection.classList.contains('hidden')) {
        if (userDropdown && !userDropdown.contains(event.target)) {
            closeUserDropdown();
        }
    }
});

function openCoursePanel() {
    closeUserDropdown();
    window.location.href = 'ModeladoHTML/create_edit_course_config.html';
}

