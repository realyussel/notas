<?php

class Jotter
{
    protected $notebooks,
    $notebooksFile,
    $notebook,
    $safeNbPath,
    $notebookFile,
        $notebookName;

    public function __construct()
    {
        $this->notebooksFile = ROOT . '/data/notebooks.json';
    }

    // Codifica cada segmento del path
    private function safeEncodePath($path)
    {
        $segments         = explode('/', $path);
        $encoded_segments = array_map('urlencode', $segments);
        return implode('/', $encoded_segments);
    }

    // Decodifica cada segmento del path
    private function safeDecodePath($path)
    {
        $segments         = explode('/', $path);
        $decoded_segments = array_map('urldecode', $segments);
        return implode('/', $decoded_segments);
    }

    /**
     * Load the list of notebooks file
     * @return array List of notebooks (name + user)
     */
    public function loadNotebooks()
    {

        $this->notebooks = Utils::loadJson($this->notebooksFile);
        if (! is_array($this->notebooks)) {
            $this->notebooks = [];
        }

        return $this->notebooks;
    }

    /**
     * Load a notebook config file
     * @param  string $name Notebook's name
     * @param  string $user Owner's login
     * @return array Notebook's configuration
     */
    public function loadNotebook($name, $user)
    {
        if (strpos($name, '..') !== false) {
            return false;
        }

        $this->notebookName = $name;
        $this->safeNbPath   = ROOT . '/data/' . urlencode($user) . '/' . urlencode($this->notebookName);
        $this->notebookFile = $this->safeNbPath . '/notebook.json';
        $this->notebook     = Utils::loadJson($this->notebookFile);

        return $this->notebook;
    }

    /**
     * Add or Edit a notebook
     * @param string  $name   New notebook name
     * @param string  $user   Owner's login
     * @return array          List of notebooks
     */
    public function setNotebook(array $params)
    {

        // Valores por defecto para los parámetros opcionales
        $defaults = [
            'name'             => '',
            'new_name'         => false,
            'user'             => '',
            'color'            => '#FFFFFF',
            'site_name'        => '',
            'site_description' => '',
            'home_route'       => '',
            'password'         => '',
            'display_chapter'  => false,
            'display_index'    => false,
            'rename'           => false,
        ];

        // Mezclar los parámetros proporcionados con los valores por defecto
        $params = array_merge($defaults, $params);

        // Asignar los parámetros a variables
        extract($params);

        // Validación inicial
        if (strpos($name, '..') !== false) {
            return false;
        }

        // Carga de cuadernos existentes
        $this->loadNotebooks();
        $absPath     = ROOT . '/data/' . urlencode($user) . '/';
        $safeName    = urlencode($name);
        $safeNewName = urlencode($new_name);

        if ($new_name !== false) {

            // Rename

            rename($absPath . $safeName, $absPath . $safeNewName);
            unset($this->notebooks[$user][$safeName]);
            // TODO add new name
            $locName = $safeNewName;
        } else {
            $locName = $safeName;
        }
        $this->safeNbPath   = $absPath . $locName;
        $this->notebookFile = $this->safeNbPath . '/notebook.json';

        $this->notebooks[$user][$locName] = $color;   // Crea o Actualiza el color
        Utils::natcaseksort($this->notebooks[$user]); // Re-ordenar cuadernos

        if (! file_exists($this->safeNbPath)) {
            // Crea un nuevo cuaderno
            $defaultNote = urlencode('Léeme.md');
            mkdir($this->safeNbPath, 0700, true);
            touch($this->safeNbPath . '/' . $defaultNote);

            $this->notebook = [
                'created'          => time(),
                'user'             => $user,
                'tree'             => [
                    $defaultNote => [
                        "chapter"   => "",
                        "slug"      => "",
                        "hidden"    => false,
                        "unindexed" => false,
                    ],
                ],
                'site_name'        => $site_name,
                'site_description' => $site_description,
                'home_route'       => $home_route,
                'password'         => $password,
                'display_chapter'  => $display_chapter,
                'display_index'    => $display_index,
                'color'            => $color,
            ];
        } else {
            // Actualiza el cuaderno
            $this->notebook                     = Utils::loadJson($this->notebookFile);
            $this->notebook['site_name']        = $site_name;
            $this->notebook['site_description'] = $site_description;
            $this->notebook['home_route']       = $home_route;
            $this->notebook['password']         = $password;
            $this->notebook['display_chapter']  = $display_chapter;
            $this->notebook['display_index']    = $display_index;
            $this->notebook['color']            = $color;
        }

        $this->notebook['updated'] = time();

        // Guardar los archivos JSON
        Utils::saveJson($this->notebooksFile, $this->notebooks);
        Utils::saveJson($this->notebookFile, $this->notebook);

        return $locName;
    }

    /**
     * Remove a notebook and everything in it
     * @param  string $name notebook name
     * @param  string $user Owner's login
     * @return boolean      true on success
     */
    public function unsetNotebook($name, $user)
    {
        $safeNb          = urlencode($user) . '/' . urlencode($name);
        $this->notebooks = Utils::unsetArrayItem($this->notebooks, $safeNb);
        $absPath         = ROOT . '/data/' . $safeNb . '/';
        return Utils::rmdirRecursive($absPath)
        && Utils::saveJson($this->notebooksFile, $this->notebooks);
    }

    /**
     * Move an item to a different location
     * @param string $sourcePath path to the item to move (can be a note or directory, never empty)
     * @param string $destPath   path to destination (must be a directory or empty for the notebook root)
     */
    public function moveItem($sourcePath, $destPath)
    {
        // NOTE: Ambos Paths estan codificados

        $success  = true;
        $itemName = basename($sourcePath);

        // Verificar si la ruta de origen y destino son realmente diferentes
        if ($sourcePath != $destPath . '/' . $itemName) {
            // Renombrar el ítem (mover)
            $sourceFullPath = $this->safeNbPath . '/' . $sourcePath;
            $destFullPath   = $this->safeNbPath . '/' . $destPath . '/' . $itemName;

            // Crear directorios de destino si no existen
            $destDir = dirname($destFullPath);
            if (! is_dir($destDir)) {
                mkdir($destDir, 0777, true);
            }

            // Verificar si el destino contiene un archivo con el mismo nombre y generar un nombre único si es necesario
            $destFullPath = $this->getUniqueDestinationPath($destFullPath);

            // Mover el ítem
            $success = rename($sourceFullPath, $destFullPath);

            // Cambiar la clave correspondiente en el array del árbol
            if ($success) {
                $this->updateTreePaths($sourcePath, $destPath . '/' . $itemName);

                // Actualizar la marca de tiempo de la libreta
                $this->notebook['updated'] = time();

                // Guardar los cambios en el archivo JSON
                $success = Utils::saveJson($this->notebookFile, $this->notebook);
            }
        }

        return $success;
    }

    private function updateTreePaths($oldPath, $newPath)
    {
        $oldPath = trim($oldPath, '/');
        $newPath = trim($newPath, '/');

        // Obtener los ítems del árbol en la ruta antigua
        $item = Utils::getArrayItem($this->notebook['tree'], $oldPath);

        // Si el ítem es un directorio, actualizar recursivamente sus contenidos
        if (is_array($item)) {
            foreach ($item as $key => $value) {
                $subOldPath = $oldPath . '/' . $key;
                $subNewPath = $newPath . '/' . $key;
                $this->updateTreePaths($subOldPath, $subNewPath);
            }
        }

        // Establecer el ítem en la nueva ubicación y eliminarlo de la ubicación antigua
        $this->notebook['tree'] = Utils::setArrayItem($this->notebook['tree'], $newPath, $item);
        $this->notebook['tree'] = Utils::unsetArrayItem($this->notebook['tree'], $oldPath);
    }

    private function getUniqueDestinationPath($destFullPath)
    {
        $pathInfo  = pathinfo($destFullPath);
        $dirname   = $pathInfo['dirname'];
        $basename  = $pathInfo['basename'];
        $filename  = $pathInfo['filename'];
        $extension = isset($pathInfo['extension']) ? '.' . $pathInfo['extension'] : '';

        $counter = 1;
        while (file_exists($destFullPath)) {
            $destFullPath = $dirname . '/' . $filename . ' (' . $counter . ')' . $extension;
            $counter++;
        }

        return $destFullPath;
    }

    public function loadItem($path)
    {
        return Utils::getArrayItem($this->notebook['tree'], $this->safeEncodePath($path)); // Obtiene el item
    }

    // Función para actualizar múltiples valores usando una ruta
    public function updateValues(&$array, $itemPath, $updates)
    {
        $itemParts = explode('/', $itemPath);
        return $this->recursiveUpdate($array, $itemParts, $updates);
    }

    private function recursiveUpdate(&$array, $itemParts, $updates)
    {
        $currentPart = array_shift($itemParts);

        if (isset($array[$currentPart])) {
            if (empty($itemParts)) {
                // Última parte de la ruta, aplicar actualizaciones solo a los valores existentes
                if (is_array($array[$currentPart])) {
                    foreach ($updates as $updateKey => $updateValue) {
                        if (isset($array[$currentPart][$updateKey])) {
                            $array[$currentPart][$updateKey] = $updateValue;
                        }
                    }
                    return true;
                }
                return false; // No es un array, no se pueden aplicar actualizaciones
            } else {
                // Continuar recursión
                return $this->recursiveUpdate($array[$currentPart], $itemParts, $updates);
            }
        } elseif (is_array($array)) {
            // Buscar en subarrays
            foreach ($array as &$value) {
                if (is_array($value)) {
                    if ($this->recursiveUpdate($value, $itemParts, $updates)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    // Función para renombrar un directorio o archivo usando rutas
    public function renameItem(&$array, $oldItemPath, $newItemPath)
    {
        $oldItemParts = explode('/', $oldItemPath);
        $newItemParts = explode('/', $newItemPath);

        if (count($oldItemParts) !== count($newItemParts)) {
            return false; // Las rutas no coinciden en longitud
        }
        return $this->recursiveRename($array, $oldItemParts, $newItemParts);
    }

    private function recursiveRename(&$array, $oldItemParts, $newItemParts)
    {
        $currentOldPart = array_shift($oldItemParts);
        $currentNewPart = array_shift($newItemParts);
        if (isset($array[$currentOldPart])) {
            if (empty($oldItemParts)) {
                // Última parte de la ruta, renombrar el elemento
                $array[$currentNewPart] = $array[$currentOldPart];
                unset($array[$currentOldPart]);
                return true;
            } else {
                // Continuar recursión
                return $this->recursiveRename($array[$currentOldPart], $oldItemParts, $newItemParts);
            }
        }
        return false;
    }

    /**
     * Add or edit (rename) a directory
     * @param string $path    Relative path to directory
     * @param string $newName New name for directory, false if not needed
     * @return boolean        True on success
     */
    public function setItem(array $params)
    {

        // Valores por defecto para los parámetros opcionales
        $defaults = [
            'name'      => '',
            'new_name'  => false,
            'chapter'   => '',
            'slug'      => '',
            'hidden'    => false,
            'unindexed' => false,
        ];
        $rename = false;

        $params = array_merge($defaults, $params); // Mezclar los parámetros proporcionados con los valores por defecto
        extract($params);                          // Asignar los parámetros a variables

        // NOTE: $name es un path sin codificar
        $safeName = $this->safeEncodePath($name);
        $absPath  = $this->safeNbPath . '/' . $safeName;
        // $item = $this->loadItem($name); // loadItem usa safeEncodePath

        if ($new_name !== false) {
            $rename      = true;
            $safeNewName = $this->safeEncodePath($new_name);
        }

        // New item

        $values = [
            "chapter"   => $chapter,
            "slug"      => $slug,
            "hidden"    => $hidden,
            "unindexed" => $unindexed,
        ];

        $isNote = substr($name, -3) == '.md';
        $exists = $isNote ? file_exists($absPath) : is_dir($absPath);

        if (! $exists && ! $rename) {

            // ADD NEW

            if ($isNote) {
                $success = touch($absPath); // Crea el archivo
            } else {
                $success = mkdir($absPath, 0700, true); // Crea el subdirectorio
                if ($success) {
                    $filePath = $absPath . DIRECTORY_SEPARATOR . 'index.md';
                    $success  = touch($filePath);
                }
            }
            $this->notebook['tree'] = Utils::setArrayItem($this->notebook['tree'], $safeName, $values); // TODO
        } else {

            // UPDATE

            $this->notebook = Utils::loadJson($this->notebookFile);
            $this->updateValues($this->notebook['tree'], $safeName, $values);
            if ($rename) {
                $success = rename($absPath, ($this->safeNbPath . '/' . $safeNewName));
                $this->renameItem($this->notebook['tree'], $safeName, $safeNewName);
            }
        }
        $this->notebook['updated'] = time();
        $success                   = Utils::saveJson($this->notebookFile, $this->notebook);

        return $success;
    }

    /**
     * Set the text of a note
     * @param string $path Relative path to note (with extension)
     * @return boolean     True on success
     */
    public function setNote($path, $text)
    {
        $absPath = $this->safeNbPath . '/' . $this->safeEncodePath($path);
        return Utils::saveFile($absPath, $text);
/*
$absPath = $this->safeNbPath . '/debug.md';
return Utils::saveFile($absPath, $this->safeEncodePath('hércules.md'));
 */
    }

    /**
     * Load (and return) a note content
     * @param  string  $path  relative path to note
     * @param  boolean $parse force Markdown to HTML parsing
     * @return string         note content
     */
    public function loadNote($path, $parse = false)
    {
        $content = Utils::loadFile($this->safeNbPath . '/' . $this->safeEncodePath($path));

        //convert Markdown to HTML
        if ($content !== false && $parse) {
            $content = \Michelf\MarkdownExtra::defaultTransform($content);
        }

        return $content;
    }

    /**
     * Delete a note/directory (file and occurence in json)
     * @param  string    $path relative path to note
     * @param  boolean    $isNote
     * @return boolean    true on success
     */
    public function unsetItem($path, $isNote)
    {
        $safePath                  = $this->safeEncodePath($path);
        $this->notebook['tree']    = Utils::unsetArrayItem($this->notebook['tree'], $safePath);
        $this->notebook['updated'] = time();
        $absPath                   = $this->safeNbPath . '/' . $safePath;

        if ($isNote) {
            if (! file_exists($absPath)) {
                // El archivo no existe...
                return false;
            }
            return unlink($absPath)
            && Utils::saveJson($this->notebookFile, $this->notebook);
        } else {
            return Utils::rmdirRecursive($absPath)
            && Utils::saveJson($this->notebookFile, $this->notebook);
        }

    }
}
