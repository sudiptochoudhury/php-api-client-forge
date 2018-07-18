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

### 0.0.6
- Minor fix to remove debug logs

### 0.0.7
- Fix for @method doc generation 
- skip description in @method doc

### 0.0.8
- Fix for @method doc generation & markdown doc generation by removing redundant spaces and newlines 
- Introduce shorter description in @method doc

### 0.0.9
- Escape pipe characters in table cell values in markdown 
- Fix query parameter inclusion in API Definition

### 0.1.0
- Change column order for parameters in Markdown documentation   

### 0.1.1
- Change column order for parameters in Markdown documentation

### 0.1.2
- Add / before endpoint in markdown documentation

### 0.1.3
- Shorten MD documentation to have 3 columns merge Method and Endpoint
- Endpoint in MD Documentation now may have links to external Documentation (if available as [API DOC] link in description)
        
### 0.2.0
- Split code into traits and helper classes
- Ability to apply filtering functions on api names, description etc. while generating JSON and Docs  
        