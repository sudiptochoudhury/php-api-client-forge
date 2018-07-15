### Changelog

### 0.0.1
- Initial release 
- Load API Definitions from json file.
- Import API Definition json from Postman exported json file (Collection v2.1)

### 0.0.2
- Change private access specifier to protected  
- Introduce properties to set default Definition `.json` and source (Postman) `.json`
- Fix recursive merge of construction parameters with default options
- Recursive Merge child default properties
- Set child class's directory as rootPath 
- Documentation generator [under-development]

     
### 0.0.3
- Markdown Documentation generator
- PhpDoc @method declarations for API magic methods
  
### 0.0.4
- Option to set custom documentation destination paths in  `importApi` function
- Better default documentation filenames

### 0.0.5
- Minor fix for default documentation file extensions