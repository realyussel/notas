import os
import json

def remove_index_md_recursively(data):
    if isinstance(data, dict):
        if 'index.md' in data:
            del data['index.md']
        for key, value in data.items():
            remove_index_md_recursively(value)
    elif isinstance(data, list):
        for item in data:
            remove_index_md_recursively(item)

def remove_index_md_key_from_json_files(directory):
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

                # Eliminar la clave 'index.md' de forma recursiva
                remove_index_md_recursively(data)

                # Escribir el contenido actualizado de vuelta al archivo JSON
                with open(file_path, 'w', encoding='utf-8') as file:
                    json.dump(data, file, ensure_ascii=False, indent=4)
                print(f"Removed 'index.md' key from {file_path}")

# Ejemplo de uso
directory_path = 'data'
remove_index_md_key_from_json_files(directory_path)
