import os
import urllib.parse
import sys
import shutil
import re
import json
import unicodedata

# Normaliza todos los archivos y subdirectorios a NFC

def remove_accents(name):
    normalized_name = unicodedata.normalize('NFD', name)
    return ''.join(c for c in normalized_name if not unicodedata.combining(c))

def normalize_unicode_nfc(name):
    return unicodedata.normalize('NFC', name)

def rename_files_and_directories(directory):

    for root, dirs, files in os.walk(directory, topdown=False):

        # Renombrar archivos
        for filename in files:
            old_path = os.path.join(root, filename)
            # Paso 1: Eliminar acentos
            no_accent_name = remove_accents(filename)
            intermediate_path = os.path.join(root, no_accent_name)
            if old_path != intermediate_path:
                try:
                    os.rename(old_path, intermediate_path)
                    print(f'Renamed file (step 1): {old_path} -> {intermediate_path}')
                    old_path = intermediate_path  # Actualizar old_path para el segundo paso
                except OSError as e:
                    print(f'Error renaming file {old_path} to {intermediate_path}: {e}')

            # Paso 2: Aplicar normalización NFC en el nombre original
            normalized_name = normalize_unicode_nfc(filename)
            new_path = os.path.join(root, normalized_name)
            if old_path != new_path:
                try:
                    os.rename(old_path, new_path)
                    print(f'Renamed file (step 2): {old_path} -> {new_path}')
                except OSError as e:
                    print(f'Error renaming file {old_path} to {new_path}: {e}')

        # Renombrar directorios
        for dirname in dirs:
            old_path = os.path.join(root, dirname)
            # Paso 1: Eliminar acentos
            no_accent_name = remove_accents(dirname)
            intermediate_path = os.path.join(root, no_accent_name)
            if old_path != intermediate_path:
                try:
                    os.rename(old_path, intermediate_path)
                    print(f'Renamed directory (step 1): {old_path} -> {intermediate_path}')
                    old_path = intermediate_path  # Actualizar old_path para el segundo paso
                except OSError as e:
                    print(f'Error renaming directory {old_path} to {intermediate_path}: {e}')

            # Paso 2: Aplicar normalización NFC en el nombre original
            normalized_name = normalize_unicode_nfc(dirname)
            new_path = os.path.join(root, normalized_name)
            if old_path != new_path:
                try:
                    os.rename(old_path, new_path)
                    print(f'Renamed directory (step 2): {old_path} -> {new_path}')
                except OSError as e:
                    print(f'Error renaming directory {old_path} to {new_path}: {e}')

# Organiza los archivos con capitulos en carpetas, crea archivos index.md

def organizar_archivos_md(directorio):
    archivos = [f for f in os.listdir(directorio) if f.endswith('.md')]
    capitulos = {}

    # Procesar capítulos principales
    for archivo in archivos:
        match = re.match(r'^(\d+)\. (.+)\.md$', archivo)
        if match:
            capitulo = match.group(1)
            nombre_base = match.group(2)
            carpeta_destino = os.path.join(directorio, nombre_base)
            
            if not os.path.exists(carpeta_destino):
                os.makedirs(carpeta_destino)
            
            nuevo_nombre = "index.md"
            shutil.move(os.path.join(directorio, archivo), os.path.join(carpeta_destino, nuevo_nombre))
            capitulos[capitulo] = carpeta_destino

    # Actualizar la lista de archivos después de mover los capítulos principales
    archivos = [f for f in os.listdir(directorio) if f.endswith('.md')]

    # Procesar subcapítulos
    for archivo in archivos:
        match = re.match(r'^(\d+)\.(\d+)\. (.+)\.md$', archivo)
        if match:
            capitulo_principal = match.group(1)
            subcapitulo = match.group(2)
            nombre_base = match.group(3)
            
            if capitulo_principal in capitulos:
                carpeta_destino = capitulos[capitulo_principal]
                nuevo_nombre = nombre_base + ".md"
                shutil.move(os.path.join(directorio, archivo), os.path.join(carpeta_destino, nuevo_nombre))

def procesar_todas_las_carpetas(directorio_principal):
    for root, dirs, files in os.walk(directorio_principal):
        for nombre_directorio in dirs:
            directorio_completo = os.path.join(root, nombre_directorio)
            organizar_archivos_md(directorio_completo)
        organizar_archivos_md(root)


# Codifica los archivos y luego las carpetas en cada nivel

def url_encode_with_plus(directory):
    for root, dirs, files in os.walk(directory, topdown=False):
        # Rename markdown files
        for file_name in files:
            if file_name.endswith('.md'):
                old_path = os.path.join(root, file_name)
                new_name = urllib.parse.quote(file_name).replace('%20', '+').replace(' ', '+')
                new_path = os.path.join(root, new_name)
                os.rename(old_path, new_path)
        
        # Rename directories
        for dir_name in dirs:
            old_path = os.path.join(root, dir_name)
            new_name = urllib.parse.quote(dir_name).replace('%20', '+').replace(' ', '+')
            new_path = os.path.join(root, new_name)
            os.rename(old_path, new_path)

# Analiza los cuadernos y crea un archivo tree.json con la estructura obtenida

def analizar_directorio(directorio):
    estructura = {}

    for root, dirs, files in os.walk(directorio):
        carpeta_actual = os.path.relpath(root, directorio)
        
        if carpeta_actual == ".":
            carpeta_actual = ""

        if carpeta_actual:
            if carpeta_actual not in estructura:
                estructura[carpeta_actual] = {
                    "chapter": "",
                    "slug": "",
                    "hidden": False,
                    "unindexed": False
                }
            destino = estructura[carpeta_actual]
        else:
            destino = estructura

        for archivo in files:
            if archivo.endswith('.md'):
                nombre_archivo = archivo
                destino[nombre_archivo] = {
                    "chapter": "",
                    "slug": "",
                    "hidden": False,
                    "unindexed": False
                }

        for subcarpeta in dirs:
            nombre_subcarpeta = subcarpeta
            subcarpeta_path = os.path.join(carpeta_actual, nombre_subcarpeta) if carpeta_actual else nombre_subcarpeta
            if subcarpeta_path not in estructura:
                estructura[subcarpeta_path] = {
                    "chapter": "",
                    "slug": "",
                    "hidden": False,
                    "unindexed": False
                }

    return estructura

def guardar_estructura_json(estructura, archivo_salida):
    with open(archivo_salida, 'w') as archivo:
        json.dump(estructura, archivo, indent=4)

# Actualiza notebook.json

def actualizar_notebook(directorio):
    notebook_path = os.path.join(directorio, 'notebook.json')
    tree_path = os.path.join(directorio, 'tree.json')

    # Leer contenido de notebook.json
    with open(notebook_path, 'r') as notebook_file:
        notebook_data = json.load(notebook_file)

    # Eliminar las propiedades especificadas si existen
    propiedades_a_eliminar = ['public', 'editor', 'safe', 'public_view']
    for propiedad in propiedades_a_eliminar:
        if propiedad in notebook_data:
            del notebook_data[propiedad]

    # Leer contenido de tree.json y asignar a la propiedad tree
    with open(tree_path, 'r') as tree_file:
        tree_data = json.load(tree_file)
        notebook_data['tree'] = tree_data

    # Guardar cambios en notebook.json
    with open(notebook_path, 'w') as notebook_file:
        json.dump(notebook_data, notebook_file, indent=4)

    # Eliminar tree.json
    os.remove(tree_path)

# Recorrer todos los subdirectorios y crear index.md si no existe

def create_index_md_in_directories(root_dir):
    for dirpath, dirnames, filenames in os.walk(root_dir):
        # Omitir el root_dir
        if dirpath == root_dir:
            continue
        index_path = os.path.join(dirpath, 'index.md')
        if 'index.md' not in filenames:
            with open(index_path, 'w') as f:
                f.write('')
            print(f'Created {index_path}')

# LOTE

def listar_directorios(directorio):
    try:
        directorios = [nombre for nombre in os.listdir(directorio) if os.path.isdir(os.path.join(directorio, nombre))]
        return directorios
    except FileNotFoundError:
        print(f"El directorio '{directorio}' no existe.")
        return []
    except NotADirectoryError:
        print(f"'{directorio}' no es un directorio.")
        return []
    except PermissionError:
        print(f"Permiso denegado para acceder al directorio '{directorio}'.")
        return []

if __name__ == "__main__":
    if len(sys.argv) != 2:
        print("Uso: python listar_directorios.py <directorio>")
        sys.exit(1)

    directorio = sys.argv[1]
    directorios = listar_directorios(directorio)

    for dir_name in directorios:
        subdir = directorio + '/' + dir_name
        # 1
        rename_files_and_directories(subdir)
        # 2
        procesar_todas_las_carpetas(subdir)
        # 3
        url_encode_with_plus(subdir)
        # 4
        estructura = analizar_directorio(subdir)
        guardar_estructura_json(estructura, subdir + '/tree.json')
        # 5
        actualizar_notebook(subdir)
        # 6
        create_index_md_in_directories(subdir)
        # END
        print(subdir)

