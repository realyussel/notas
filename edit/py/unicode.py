import os
import json
import unicodedata
import re

# Función para convertir caracteres Unicode escapados a texto plano
def unescape_unicode(text):
    def replace(match):
        return chr(int(match.group(1), 16))
    return re.sub(r'\\u([0-9a-fA-F]{4})', replace, text)

# Función para escapar solo los caracteres acentuados a Unicode y normalizar
def escape_accented_characters(text):
    normalized_text = unicodedata.normalize('NFC', text)
    return re.sub(r'[^\x00-\x7F]', lambda x: '\\u{:04x}'.format(ord(x.group())), normalized_text)

# Función para normalizar las claves a la forma compuesta
def normalize_unicode(text):
    return unicodedata.normalize('NFC', text)

def process_tree_escape(tree):
    if isinstance(tree, dict):
        return {escape_accented_characters(key): process_tree_escape(value) for key, value in tree.items()}
    elif isinstance(tree, list):
        return [process_tree_escape(element) for element in tree]
    else:
        return tree

def process_tree_unescape(tree):
    if isinstance(tree, dict):
        return {normalize_unicode(unescape_unicode(key)): process_tree_unescape(value) for key, value in tree.items()}
    elif isinstance(tree, list):
        return [process_tree_unescape(element) for element in tree]
    else:
        return tree

def normalize_tree_keys(tree):
    if isinstance(tree, dict):
        return {normalize_unicode(key): normalize_tree_keys(value) for key, value in tree.items()}
    elif isinstance(tree, list):
        return [normalize_tree_keys(element) for element in tree]
    else:
        return tree

def clean_double_backslashes(tree):
    if isinstance(tree, dict):
        return {key.replace('\\', '␍'): clean_double_backslashes(value) for key, value in tree.items()}
    elif isinstance(tree, list):
        return [clean_double_backslashes(element) for element in tree]
    else:
        return tree

def modify_json_files(directory, escape=True):
    # Recorrer todos los archivos y subdirectorios
    for root, dirs, files in os.walk(directory):
        for filename in files:
            if filename.endswith('.json'):
                file_path = os.path.join(root, filename)
                
                # Leer el contenido del archivo JSON
                with open(file_path, 'r', encoding='utf-8') as file:
                    try:
                        data = json.load(file)
                    except json.JSONDecodeError:
                        print(f"Error decoding JSON in file {file_path}")
                        continue

                # Modificar las claves del elemento 'tree'
                if 'tree' in data:
                    if escape:
                        data['tree'] = process_tree_escape(data['tree'])
                    else:
                        data['tree'] = process_tree_unescape(data['tree'])
                    
                    # Normalizar las claves a la forma compuesta
                    data['tree'] = normalize_tree_keys(data['tree'])
                    
                    # Limpiar dobles barras invertidas en cualquier parte de las claves
                    data['tree'] = clean_double_backslashes(data['tree'])

                    # Escribir el contenido actualizado de vuelta al archivo JSON
                    with open(file_path, 'w', encoding='utf-8') as file:
                        json.dump(data, file, ensure_ascii=False, indent=4)

# Ejemplo de uso
directory_path = 'data'
modify_json_files(directory_path, escape=True)  # Para escapar caracteres acentuados a Unicode
# modify_json_files(directory_path, escape=False)  # Para desescapar texto Unicode a texto plano
