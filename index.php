<?php
session_start();

// Incluir las clases 
require_once 'Libro.php';
require_once 'Biblioteca.php';

if (!isset($_SESSION['biblioteca_data'])) {
    $_SESSION['biblioteca_data'] = [
        'libros' => [],
        'prestamos' => []
    ];
}

// Reconstruir el objeto Biblioteca desde los datos de sesión
$biblioteca = new Biblioteca();
$biblioteca->cargarDesdeArray($_SESSION['biblioteca_data']);

$mensaje = '';
$tipoMensaje = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // AGREGAR LIBRO
    if (isset($_POST['agregar_libro'])) {
        $id = rand(1000, 9999);
        $titulo = trim($_POST['titulo']);
        $autor = trim($_POST['autor']);
        $categoria = trim($_POST['categoria']);
        $isbn = trim($_POST['isbn']);
        
        if (!empty($titulo) && !empty($autor) && !empty($categoria) && !empty($isbn)) {
            $libro = new Libro($id, $titulo, $autor, $categoria, $isbn);
            if ($biblioteca->agregarLibro($libro)) {
                $mensaje = 'Libro agregado exitosamente';
                $tipoMensaje = 'exito';
                // Guardar en sesión
                $_SESSION['biblioteca_data'] = $biblioteca->guardarEnArray();
            }
        }
    }
    
    // MODIFICAR LIBRO
    if (isset($_POST['modificar_libro'])) {
        $id = $_POST['libro_id'];
        $titulo = trim($_POST['titulo']);
        $autor = trim($_POST['autor']);
        $categoria = trim($_POST['categoria']);
        $isbn = trim($_POST['isbn']);
        
        if ($biblioteca->modificarLibro($id, $titulo, $autor, $categoria, $isbn)) {
            $mensaje = 'Libro modificado exitosamente';
            $tipoMensaje = 'exito';
            $_SESSION['biblioteca_data'] = $biblioteca->guardarEnArray();
        } else {
            $mensaje = 'Error al modificar el libro';
            $tipoMensaje = 'error';
        }
    }
    
    // ELIMINAR LIBRO
    if (isset($_POST['eliminar_libro'])) {
        $id = $_POST['libro_id'];
        if ($biblioteca->eliminarLibro($id)) {
            $mensaje = 'Libro eliminado exitosamente';
            $tipoMensaje = 'exito';
            $_SESSION['biblioteca_data'] = $biblioteca->guardarEnArray();
        } else {
            $mensaje = 'No se puede eliminar el libro. Puede que esté prestado o no exista.';
            $tipoMensaje = 'error';
        }
    }
    
    // PRESTAR LIBRO
    if (isset($_POST['prestar_libro'])) {
        $id_libro = $_POST['libro_id'];
        $usuario = trim($_POST['usuario']);
        
        if (!empty($usuario)) {
            $prestamo = $biblioteca->prestarLibro($id_libro, $usuario);
            if ($prestamo) {
                $mensaje = 'Libro prestado exitosamente a ' . $usuario;
                $tipoMensaje = 'exito';
                $_SESSION['biblioteca_data'] = $biblioteca->guardarEnArray();
            } else {
                $mensaje = 'No se puede prestar el libro. Puede que no esté disponible.';
                $tipoMensaje = 'error';
            }
        }
    }
    
    // DEVOLVER LIBRO
    if (isset($_POST['devolver_libro'])) {
        $id_prestamo = $_POST['prestamo_id'];
        if ($biblioteca->devolverLibro($id_prestamo)) {
            $mensaje = 'Libro devuelto exitosamente';
            $tipoMensaje = 'exito';
            $_SESSION['biblioteca_data'] = $biblioteca->guardarEnArray();
        } else {
            $mensaje = 'Error al devolver el libro';
            $tipoMensaje = 'error';
        }
    }
}

// BUSCAR LIBROS
$resultados_busqueda = [];
if (isset($_GET['buscar']) && !empty($_GET['termino_busqueda'])) {
    $resultados_busqueda = $biblioteca->buscarLibros($_GET['termino_busqueda']);
}

// CARGAR DATOS PARA MODIFICAR
$libro_a_modificar = null;
if (isset($_GET['editar'])) {
    $libro_a_modificar = $biblioteca->obtenerLibroPorId($_GET['editar']);
}

// Obtener datos para las vistas
$libros = $biblioteca->obtenerTodosLosLibros();
$prestamos = $biblioteca->obtenerTodosLosPrestamos();
$prestamos_activos = $biblioteca->obtenerPrestamosActivos();
$categorias = $biblioteca->obtenerCategorias();
$autores = $biblioteca->obtenerAutores();
$estadisticas = $biblioteca->obtenerEstadisticas();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bibliotech - Sistema de Gestión de Biblioteca</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <div class="container">
        <h1>Sistema de Gestión de Biblioteca</h1>

        <?php if ($mensaje): ?>
            <div class="mensaje mensaje-<?php echo $tipoMensaje; ?>">
                <?php echo $mensaje; ?>
            </div>
        <?php endif; ?>

        <!-- Estadísticas -->
        <div class="estadisticas">
            <div class="estadistica-item">
                <span class="numero"><?php echo $estadisticas['total_libros']; ?></span>
                <span class="label">Total Libros</span>
            </div>
            <div class="estadistica-item">
                <span class="numero"><?php echo $estadisticas['libros_disponibles']; ?></span>
                <span class="label">Disponibles</span>
            </div>
            <div class="estadistica-item">
                <span class="numero"><?php echo $estadisticas['prestamos_activos']; ?></span>
                <span class="label">Préstamos Activos</span>
            </div>
        </div>

        <!-- SECCIÓN: AGREGAR LIBRO -->
        <div class="section">
            <h3>Agregar Nuevo Libro</h3>
            <form action="" method="POST" class="form-grid">
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" name="titulo" required>
                </div>
                <div class="form-group">
                    <label for="autor">Autor:</label>
                    <input type="text" name="autor" required>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <input type="text" name="categoria" required>
                </div>
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" name="isbn" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="agregar_libro" class="btn btn-primary">Agregar Libro</button>
                </div>
            </form>
        </div>

        <!-- SECCIÓN: MODIFICAR LIBRO -->
        <?php if ($libro_a_modificar): ?>
        <div class="section">
            <h3>Modificar Libro</h3>
            <form action="" method="POST" class="form-grid">
                <input type="hidden" name="libro_id" value="<?php echo $libro_a_modificar->getId(); ?>">
                
                <div class="form-group">
                    <label for="titulo">Título:</label>
                    <input type="text" name="titulo" value="<?php echo $libro_a_modificar->getTitulo(); ?>" required>
                </div>
                <div class="form-group">
                    <label for="autor">Autor:</label>
                    <input type="text" name="autor" value="<?php echo $libro_a_modificar->getAutor(); ?>" required>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoría:</label>
                    <input type="text" name="categoria" value="<?php echo $libro_a_modificar->getCategoria(); ?>" required>
                </div>
                <div class="form-group">
                    <label for="isbn">ISBN:</label>
                    <input type="text" name="isbn" value="<?php echo $libro_a_modificar->getIsbn(); ?>" required>
                </div>
                <div class="form-group">
                    <button type="submit" name="modificar_libro" class="btn btn-warning">Modificar Libro</button>
                    <a href="index.php" class="btn btn-primary">Cancelar</a>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- SECCIÓN: BUSCAR LIBROS -->
        <div class="section">
            <h3>Buscar Libros</h3>
            <form action="" method="GET" class="buscar-form">
                <div class="buscar-input-group">
                    <div class="form-group">
                        <label for="termino_busqueda">Buscar por título, autor o categoría:</label>
                        <input type="text" name="termino_busqueda" value="<?php echo $_GET['termino_busqueda'] ?? ''; ?>" required>
                    </div>
                    <div class="btn-group">
                        <button type="submit" name="buscar" class="btn btn-primary">Buscar</button>
                        <a href="index.php" class="btn btn-secondary">Limpiar</a>
                    </div>
                </div>
            </form>

            <?php if (!empty($resultados_busqueda)): ?>
                <div class="table-container">
                    <h4>Resultados de la búsqueda (<?php echo count($resultados_busqueda); ?> encontrados):</h4>
                    <table>
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Categoría</th>
                                <th>ISBN</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($resultados_busqueda as $libro): ?>
                                <tr>
                                    <td><?php echo $libro->getId(); ?></td>
                                    <td><?php echo $libro->getTitulo(); ?></td>
                                    <td><?php echo $libro->getAutor(); ?></td>
                                    <td><?php echo $libro->getCategoria(); ?></td>
                                    <td><?php echo $libro->getIsbn(); ?></td>
                                    <td class="<?php echo $libro->getDisponible() ? 'disponible' : 'prestado'; ?>">
                                        <?php echo $libro->getDisponible() ? 'Disponible' : 'Prestado'; ?>
                                    </td>
                                    <td class="acciones">
                                        <a href="index.php?editar=<?php echo $libro->getId(); ?>" class="btn btn-warning btn-sm">Modificar</a>
                                        <form action="" method="POST" style="display: inline;">
                                            <input type="hidden" name="libro_id" value="<?php echo $libro->getId(); ?>">
                                            <button type="submit" name="eliminar_libro" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('¿Estás seguro de eliminar este libro?')">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php elseif (isset($_GET['buscar'])): ?>
                <p>No se encontraron libros que coincidan con la búsqueda.</p>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN: PRESTAR LIBRO -->
        <div class="section">
            <h3>Prestar Libro</h3>
            <form action="" method="POST" class="prestar-form">
                <div class="form-input-group">
                    <div class="form-group">
                        <label for="libro_id">Seleccionar Libro:</label>
                        <select name="libro_id" required>
                            <option value="">-- Seleccionar libro --</option>
                            <?php foreach ($biblioteca->obtenerLibrosDisponibles() as $libro): ?>
                                <option value="<?php echo $libro->getId(); ?>">
                                    <?php echo $libro->getTitulo(); ?> - <?php echo $libro->getAutor(); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="usuario">Nombre del Usuario:</label>
                        <input type="text" name="usuario" required>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="prestar_libro" class="btn btn-success">Prestar Libro</button>
                </div>
            </form>
        </div>

        <!-- SECCIÓN: DEVOLVER LIBRO -->
        <div class="section">
            <h3>Devolver Libro</h3>
            <form action="" method="POST" class="devolver-form">
                <div class="form-input-group">
                    <div class="form-group">
                        <label for="prestamo_id">Seleccionar Préstamo Activo:</label>
                        <select name="prestamo_id" required>
                            <option value="">-- Seleccionar préstamo --</option>
                            <?php foreach ($prestamos_activos as $prestamo): ?>
                                <option value="<?php echo $prestamo['id']; ?>">
                                    <?php echo $prestamo['titulo_libro']; ?> - <?php echo $prestamo['usuario']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" name="devolver_libro" class="btn btn-primary">Devolver Libro</button>
                </div>
            </form>
        </div>

        <!-- SECCIÓN: LISTA DE LIBROS -->
        <div class="section">
            <h3>Todos los Libros (<?php echo count($libros); ?>)</h3>
            <?php if (!empty($libros)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Título</th>
                                <th>Autor</th>
                                <th>Categoría</th>
                                <th>ISBN</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($libros as $libro): ?>
                                <tr>
                                    <td><?php echo $libro->getId(); ?></td>
                                    <td><?php echo $libro->getTitulo(); ?></td>
                                    <td><?php echo $libro->getAutor(); ?></td>
                                    <td><?php echo $libro->getCategoria(); ?></td>
                                    <td><?php echo $libro->getIsbn(); ?></td>
                                    <td class="<?php echo $libro->getDisponible() ? 'disponible' : 'prestado'; ?>">
                                        <?php echo $libro->getDisponible() ? 'Disponible' : 'Prestado'; ?>
                                    </td>
                                    <td class="acciones">
                                        <a href="index.php?editar=<?php echo $libro->getId(); ?>" class="btn btn-warning btn-sm">Modificar</a>
                                        <form action="" method="POST" style="display: inline;">
                                            <input type="hidden" name="libro_id" value="<?php echo $libro->getId(); ?>">
                                            <button type="submit" name="eliminar_libro" class="btn btn-danger btn-sm" 
                                                onclick="return confirm('¿Estás seguro de eliminar este libro?')">Eliminar</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay libros registrados en el sistema.</p>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN: HISTORIAL DE PRÉSTAMOS -->
        <div class="section">
            <h3>Historial de Préstamos (<?php echo count($prestamos); ?>)</h3>
            <?php if (!empty($prestamos)): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th># ID</th>
                                <th>Libro</th>
                                <th>Usuario</th>
                                <th>Fecha Préstamo</th>
                                <th>Fecha Devolución</th>
                                <th>Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prestamos as $prestamo): ?>
                                <tr>
                                    <td><?php echo $prestamo['id']; ?></td>
                                    <td><?php echo $prestamo['titulo_libro']; ?></td>
                                    <td><?php echo $prestamo['usuario']; ?></td>
                                    <td><?php echo $prestamo['fecha_prestamo']; ?></td>
                                    <td><?php echo $prestamo['fecha_devolucion'] ?: 'Pendiente'; ?></td>
                                    <td class="<?php echo $prestamo['fecha_devolucion'] === null ? 'activo' : 'devuelto'; ?>">
                                        <?php echo $prestamo['fecha_devolucion'] === null ? 'Activo' : 'Devuelto'; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No hay préstamos registrados en el sistema.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>