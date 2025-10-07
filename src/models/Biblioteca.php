<?php
/**
 * Clase Biblioteca - Maneja toda la gestión del sistema e interacción con los usuarios
 * Aplica principios de responsabilidad única y bajo acoplamiento
 */
class Biblioteca {
    private $libros;
    private $prestamos;

    public function __construct() {
        $this->libros = [];
        $this->prestamos = [];
    }

    // ========== MÉTODOS PARA GESTIÓN DE LIBROS ==========

    /**
     * Agregar un nuevo libro al sistema
     */
    public function agregarLibro($libro) {
        $this->libros[] = $libro;
        return true;
    }

    /**
     * Eliminar un libro del sistema
     */
    public function eliminarLibro($id) {
        foreach ($this->libros as $index => $libro) {
            if ($libro->getId() == $id) {
                if (!$libro->getDisponible()) {
                    return false; // No se puede eliminar un libro prestado
                }
                array_splice($this->libros, $index, 1);
                return true;
            }
        }
        return false;
    }

    /**
     * Modificar un libro existente
     */
    public function modificarLibro($id, $titulo, $autor, $categoria, $isbn) {
        foreach ($this->libros as $libro) {
            if ($libro->getId() == $id) {
                $libro->actualizar($titulo, $autor, $categoria, $isbn);
                return true;
            }
        }
        return false;
    }

    /**
     * Buscar libros por término (título, autor o categoría)
     */
    public function buscarLibros($termino) {
        $resultados = [];
        foreach ($this->libros as $libro) {
            if ($libro->coincideBusqueda($termino)) {
                $resultados[] = $libro;
            }
        }
        return $resultados;
    }

    /**
     * Obtener libro por ID
     */
    public function obtenerLibroPorId($id) {
        foreach ($this->libros as $libro) {
            if ($libro->getId() == $id) {
                return $libro;
            }
        }
        return null;
    }

    /**
     * Obtener todos los libros
     */
    public function obtenerTodosLosLibros() {
        return $this->libros;
    }

    /**
     * Obtener libros disponibles para préstamo
     */
    public function obtenerLibrosDisponibles() {
        $disponibles = [];
        foreach ($this->libros as $libro) {
            if ($libro->getDisponible()) {
                $disponibles[] = $libro;
            }
        }
        return $disponibles;
    }

    // ========== MÉTODOS PARA GESTIÓN DE PRÉSTAMOS ==========

    /**
     * Prestar un libro a un usuario
     */
    public function prestarLibro($idLibro, $usuario) {
        $libro = $this->obtenerLibroPorId($idLibro);
        
        if ($libro && $libro->prestar()) {
            $prestamo = [
                'id' => rand(1000, 9999),
                'id_libro' => $idLibro,
                'titulo_libro' => $libro->getTitulo(),
                'usuario' => $usuario,
                'fecha_prestamo' => date('Y-m-d H:i:s'),
                'fecha_devolucion' => null
            ];
            $this->prestamos[] = $prestamo;
            return $prestamo;
        }
        return false;
    }

    /**
     * Devolver un libro prestado
     */
    public function devolverLibro($idPrestamo) {
        foreach ($this->prestamos as &$prestamo) {
            if ($prestamo['id'] == $idPrestamo && $prestamo['fecha_devolucion'] === null) {
                $prestamo['fecha_devolucion'] = date('Y-m-d H:i:s');
                
                $libro = $this->obtenerLibroPorId($prestamo['id_libro']);
                if ($libro) {
                    $libro->devolver();
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Obtener préstamos activos
     */
    public function obtenerPrestamosActivos() {
        $activos = [];
        foreach ($this->prestamos as $prestamo) {
            if ($prestamo['fecha_devolucion'] === null) {
                $activos[] = $prestamo;
            }
        }
        return $activos;
    }

    /**
     * Obtener todos los préstamos
     */
    public function obtenerTodosLosPrestamos() {
        return $this->prestamos;
    }

    // ========== MÉTODOS DE INFORMACIÓN DEL SISTEMA ==========

    /**
     * Obtener estadísticas del sistema
     */
    public function obtenerEstadisticas() {
        $totalLibros = count($this->libros);
        $librosDisponibles = count($this->obtenerLibrosDisponibles());
        $prestamosActivos = count($this->obtenerPrestamosActivos());

        return [
            'total_libros' => $totalLibros,
            'libros_disponibles' => $librosDisponibles,
            'libros_prestados' => $totalLibros - $librosDisponibles,
            'prestamos_activos' => $prestamosActivos
        ];
    }

    /**
     * Obtener categorías únicas de los libros
     */
    public function obtenerCategorias() {
        $categorias = [];
        foreach ($this->libros as $libro) {
            $categoria = $libro->getCategoria();
            if (!in_array($categoria, $categorias)) {
                $categorias[] = $categoria;
            }
        }
        return $categorias;
    }

    /**
     * Obtener autores únicos de los libros
     */
    public function obtenerAutores() {
        $autores = [];
        foreach ($this->libros as $libro) {
            $autor = $libro->getAutor();
            if (!in_array($autor, $autores)) {
                $autores[] = $autor;
            }
        }
        return $autores;
    }

    // ========== MÉTODOS PARA PERSISTENCIA ==========

    /**
     * Guardar datos en array para sesión
     */
    public function guardarEnArray() {
        $librosData = [];
        foreach ($this->libros as $libro) {
            $librosData[] = [
                'id' => $libro->getId(),
                'titulo' => $libro->getTitulo(),
                'autor' => $libro->getAutor(),
                'categoria' => $libro->getCategoria(),
                'isbn' => $libro->getIsbn(),
                'disponible' => $libro->getDisponible()
            ];
        }

        return [
            'libros' => $librosData,
            'prestamos' => $this->prestamos
        ];
    }

    /**
     * Cargar datos desde array de sesión
     */
    public function cargarDesdeArray($data) {
        // Cargar libros
        if (isset($data['libros'])) {
            foreach ($data['libros'] as $libroData) {
                $libro = new Libro(
                    $libroData['id'],
                    $libroData['titulo'],
                    $libroData['autor'],
                    $libroData['categoria'],
                    $libroData['isbn']
                );
                // Restaurar estado de disponibilidad
                if (!$libroData['disponible']) {
                    $libro->prestar();
                }
                $this->libros[] = $libro;
            }
        }

        // Cargar préstamos
        if (isset($data['prestamos'])) {
            $this->prestamos = $data['prestamos'];
        }
    }
}
?>