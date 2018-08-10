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

### 0.2.1
- Add option to override import class in Client class import call 
        
### 0.2.2
- Redistribute import from JSON into multible extensible functions
- Cleaner code

### 0.2.3
- Option to skip an item
- Option to skip generation of Documentation 
- Enhancement of filters
- Option to add parameters default values externally 
- Documentation shows parameter default values
- Documentation formatting with inline code style for methods and parameters 


### 0.2.4
- Made all function protected expect for `importApi` to prevent conflict with api methods
- Documentation


### 0.2.5
- Documentation minor update

### 0.2.6
- Fix missing default values in MD Documentation

### 0.3.0 - Added Logger handler stack, logger can be through constructor parameters ('settings.log'). Set false to disable logger.
- Added way to add Request and Response Handler functions from constructor paramters ('settings.requestHandler' && 'settings.responseHandler').

### 0.3.1 - Logger Handler fix

### 0.3.2 - Response and Request handler can now be defined as a class member

### 0.3.3 - Fix logger path

### 0.3.4 - Fix incorrect json_decoder and encoder