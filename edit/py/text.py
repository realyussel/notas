import os
import json
import re

# Función para convertir texto escapado Unicode a texto plano
def unescape_unicode(text):
    def replace(match):
        return chr(int(match.group(1), 16))
    return re.sub(r'\\u([0-9a-fA-F]{4})', replace, text)

def process_tree(tree):
    if isinstance(tree, dict):
        return {unescape_unicode(key): process_tree(value) for key, value in tree.items()}
    elif isinstance(tree, list):
        return [process_tree(element) for element in tree]
    else:
        return tree

def modify_json_files(directory):
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
                    data['tree'] = process_tree(data['tree'])

                    # Escribir el contenido actualizado de vuelta al archivo JSON
                    with open(file_path, 'w', encoding='utf-8') as file:
                        json.dump(data, file, ensure_ascii=False, indent=4)
                    print(f"Modified 'tree' keys in {file_path}")

# Ejemplo de uso
directory_path = 'data'
modify_json_files(directory_path)
