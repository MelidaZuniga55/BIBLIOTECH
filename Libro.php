<?php
/**
 * Clase Libro - Representa un libro en el sistema de biblioteca
 * Implementa encapsulación y métodos específicos para gestión de libros
 */
class Libro {
    private $id;
    private $titulo;
    private $autor;
    private $categoria;
    private $isbn;
    private $disponible;

    public function __construct($id, $titulo, $autor, $categoria, $isbn) {
        $this->id = $id;
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->isbn = $isbn;
        $this->disponible = true;
    }

    // Getters
    public function getId() { return $this->id; }
    public function getTitulo() { return $this->titulo; }
    public function getAutor() { return $this->autor; }
    public function getCategoria() { return $this->categoria; }
    public function getIsbn() { return $this->isbn; }
    public function getDisponible() { return $this->disponible; }

    // Setters
    public function setTitulo($titulo) { $this->titulo = $titulo; }
    public function setAutor($autor) { $this->autor = $autor; }
    public function setCategoria($categoria) { $this->categoria = $categoria; }
    public function setIsbn($isbn) { $this->isbn = $isbn; }

    // Métodos específicos para gestión de libros
    public function prestar() {
        if ($this->disponible) {
            $this->disponible = false;
            return true;
        }
        return false;
    }

    public function devolver() {
        $this->disponible = true;
    }

    public function actualizar($titulo, $autor, $categoria, $isbn) {
        $this->titulo = $titulo;
        $this->autor = $autor;
        $this->categoria = $categoria;
        $this->isbn = $isbn;
    }

    /**
     * Buscar coincidencias en el libro
     * @param string $termino Término de búsqueda
     * @return bool True si coincide
     */
    public function coincideBusqueda($termino) {
        $termino = strtolower($termino);
        return strpos(strtolower($this->titulo), $termino) !== false ||
               strpos(strtolower($this->autor), $termino) !== false ||
               strpos(strtolower($this->categoria), $termino) !== false;
    }
}
?>