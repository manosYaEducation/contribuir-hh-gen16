/* ===== VARIABLES GLOBALES ===== */
var isLoggedIn = false;
var currentUser = null;
var currentRole = null;  // Variable para almacenar el rol
var selectedCategory = "Todas";
var coursesData = []; // Variable para almacenar los cursos
var currentPage = 1; // Página actual para paginación
var coursesPerPage = 6; // Cursos por página, ajustar según necesidad

/* ===== INICIALIZACION ===== */
document.addEventListener('DOMContentLoaded', async function() {
    console.log('- Iniciando aplicación...');
    await checkSessionStatus();  // esperar que el rol esté disponible
    init();

    // Conectar los formularios a las funciones de JS
    const loginForm = document.getElementById('loginForm');
    const registerForm = document.getElementById('registerForm');

    if (loginForm) loginForm.addEventListener('submit', handleLogin);
    if (registerForm) registerForm.addEventListener('submit', handleRegister);

    // Detectar si viene desde curso.html sin autenticación
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('openLoginModal') === '1') {
        openModal('loginModal');
        // Limpiar el parámetro de la URL para que no se vuelva a abrir
        window.history.replaceState({}, document.title, window.location.pathname);
    }
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
    if (modalId === 'loginModal') {
        const pwd = document.getElementById('loginPassword');
        if (pwd) pwd.value = '';
    }
}

function switchToModal(fromModalId, toModalId) {
    closeModal(fromModalId);
    openModal(toModalId);
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
        if (event.target.id === 'loginModal') {
            const pwd = document.getElementById('loginPassword');
            if (pwd) pwd.value = '';
        }
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
    const submitBtn = form.querySelector('button[type="submit"]'); // ← queda esta
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
            currentRole = result.role; // Store the user role from login response
            updateUserUI();
            
            // Mostrar panel de creación solo si es admin o instructor
            if (currentRole && (currentRole === 'admin' || currentRole === 'instructor')) {
                const coursePanelBtn = document.getElementById('course-panel-btn');
                if (coursePanelBtn) coursePanelBtn.style.display = 'block';
            }
            
            // Recargar cursos para actualizar detalles según el nuevo rol
            await loadCoursesData();
            renderCourses();
            
            closeModal('loginModal');
            form.reset();
        } else if (response.status === 429) {
            const segundosRestantes = result.retry_after || 900;
            // const submitBtn = ...  ← SACAR ESTA LÍNEA
            const rateLimitMsg = document.getElementById('loginRateLimitMsg');
            const countdownEl = document.getElementById('loginCountdown');

            if (submitBtn) submitBtn.disabled = true;
            rateLimitMsg.style.display = 'block';

            let segundos = segundosRestantes;
            const intervalo = setInterval(() => {
                const minutos = Math.floor(segundos / 60);
                const segs = segundos % 60;
                countdownEl.textContent = `${minutos}:${segs.toString().padStart(2, '0')}`;

                if (segundos <= 0) {
                    clearInterval(intervalo);
                    rateLimitMsg.style.display = 'none';
                    if (submitBtn) submitBtn.disabled = false;
                }
                segundos--;
            }, 1000);
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
                currentRole = data.role;  // Almacenar el rol del usuario
                updateUserUI();

                // Mostrar panel de creación solo si es admin o instructor
                if (data.role === 'admin' || data.role === 'instructor') {
                    const coursePanelBtn = document.getElementById('course-panel-btn');
                    if (coursePanelBtn) coursePanelBtn.style.display = 'block';
                }
            } else {
                // No hay sesión activa, resetear UI
                isLoggedIn = false;
                currentUser = null;
                currentRole = null;
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
    currentRole = null;  // Resetear el rol
    
    // Ocultar el botón del panel de creación
    const coursePanelBtn = document.getElementById('course-panel-btn');
    if (coursePanelBtn) coursePanelBtn.style.display = 'none';
    
    // Recargar cursos para actualizar detalles según el rol reseteado
    await loadCoursesData();
    renderCourses();
    
    updateUserUI();
}

function updateUserUI() {
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const userSection = document.getElementById('userSection');
    const userName = document.getElementById('userName');
    const coursePanelBtn = document.getElementById('course-panel-btn');

    if (isLoggedIn && currentUser) {
        loginBtn.style.display = 'none';
        registerBtn.style.display = 'none';
        userSection.classList.remove('hidden');
        userName.textContent = 'Hola, ' + currentUser.name;
        
        // Solo mostrar botón de panel si es admin o instructor
        if (coursePanelBtn && currentRole && (currentRole === 'admin' || currentRole === 'instructor')) {
            coursePanelBtn.style.display = 'block';
        } else if (coursePanelBtn) {
            coursePanelBtn.style.display = 'none';
        }
    } else {
        loginBtn.style.display = 'block';
        registerBtn.style.display = 'block';
        userSection.classList.add('hidden');
        
        // Asegurar que el botón de panel esté oculto cuando no hay sesión
        if (coursePanelBtn) {
            coursePanelBtn.style.display = 'none';
        }
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
    window.addEventListener('scroll', () => {
        updateActiveNav();
        const header = document.getElementById('header');
        if (window.scrollY > 20) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
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
    currentPage = 1;
    document.querySelectorAll('.category-btn').forEach(btn => btn.classList.remove('active'));
    clickedButton.classList.add('active');
    renderCourses();
}

function filterCourses() {
    currentPage = 1;
    renderCourses();
}

function renderCourses() {
    const grid = document.getElementById('coursesGrid');
    const pagination = document.getElementById('paginationControls');
    const searchValue = document.getElementById('searchCourses').value.toLowerCase();
    grid.innerHTML = '';
    if (pagination) pagination.innerHTML = '';

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

    const totalPages = Math.max(1, Math.ceil(filtered.length / coursesPerPage));
    if (currentPage > totalPages) currentPage = totalPages;

    const start = (currentPage - 1) * coursesPerPage;
    const end = start + coursesPerPage;
    const visibleCourses = filtered.slice(start, end);

    visibleCourses.forEach(course => grid.appendChild(createCourseCard(course)));
    renderPagination(totalPages);
}

function renderPagination(totalPages) {
    const pagination = document.getElementById('paginationControls');
    if (!pagination) return;

    pagination.innerHTML = '';
    if (totalPages <= 1) return;

    const createButton = (label, page, disabled = false, active = false) => {
        const btn = document.createElement('button');
        btn.textContent = label;
        btn.className = 'pagination-btn';
        if (active) btn.classList.add('active');
        if (disabled) {
            btn.disabled = true;
            btn.classList.add('disabled');
        } else {
            btn.addEventListener('click', () => {
                currentPage = page;
                renderCourses();
                window.scrollTo({ top: document.getElementById('cursos').offsetTop - 100, behavior: 'smooth' });
            });
        }
        return btn;
    };

    const prevBtn = createButton('← Anterior', Math.max(1, currentPage - 1), currentPage === 1);
    pagination.appendChild(prevBtn);

    for (let i = 1; i <= totalPages; i++) {
        const pageBtn = createButton(i.toString(), i, false, i === currentPage);
        pagination.appendChild(pageBtn);
    }

    const nextBtn = createButton('Siguiente →', Math.min(totalPages, currentPage + 1), currentPage === totalPages);
    pagination.appendChild(nextBtn);
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
                <span class="rating-stars">⭐ ${(course.rating != null ? parseFloat(course.rating) : 0).toFixed(1)}</span>
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
            ${course.show_details ? `
            <a href="pages/curso2.html?id=${course.id}" class="btn-course-details" style="text-decoration:none;">
                Ver Detalles
            </a>
        ` : ''}
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

