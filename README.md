# php-api-client-forge

Create restful API client from Postman Collection data (or other sources) in PHP with least code.
# php-api-client-forge

- Create restful API client class from Postman Collection data (or other sources)  
- Auto generate class `@method` documentation for phpDoc 
- Auto generate Markdown API Documentation
 
 

[![Latest Stable Version](https://poser.pugx.org/sudiptochoudhury/php-api-client-forge/version)](https://packagist.org/packages/sudiptochoudhury/php-api-client-forge)
[![Latest Unstable Version](https://poser.pugx.org/sudiptochoudhury/php-api-client-forge/v/unstable)](//packagist.org/packages/sudiptochoudhury/php-api-client-forge)
[![License](https://poser.pugx.org/sudiptochoudhury/php-api-client-forge/license)](https://packagist.org/packages/sudiptochoudhury/php-api-client-forge)
[![Total Downloads](https://poser.pugx.org/sudiptochoudhury/php-api-client-forge/downloads)](https://packagist.org/packages/sudiptochoudhury/php-api-client-forge)
[![composer.lock available](https://poser.pugx.org/sudiptochoudhury/php-api-client-forge/composerlock)](https://packagist.org/packages/sudiptochoudhury/php-api-client-forge)

Import API definitions from Postman exported JSON to a JSON compatible with Guzzle\Description format 
or pass a `GuzzleHttp\Command\Guzzle\Description` object and 
your API class is ready to roll. 

> As a bonus, you will also get an auto generated API documentation in markdown format. Moreover, you will get a php 
file with `@method` declarations for phpDoc class documentation. 

This package needs to be used as parent. Extend the `Client` class to make you own API class. 

> This is specially 
designed to import Postman collection exported as JSON (Collection v2.1 - 
[Schema](https://schema.getpostman.com/json/collection/v2.1.0/collection.json)). 


<a name="basics"/>

## Basics

```php

<?php

namespace My\Namespace\ApiProvider;

use SudiptoChoudhury\Support\Forge\Api\Client as ApiForge;

class Api extends ApiForge
{

    protected $DEFAULT_API_JSON_PATH = './config/GuzzleDescription.json';
    
    protected $DEFAULTS = [
        'AuthToken' => 'xxxxxxxxxxxxxxxxxxxxxxxxxx',
        'ClientID' => 'xxxxxxxxxxxxxxx',
        'client' => [
            'base_uri' => 'https://api.provider.com/api/v1/',
            'verify' => false,
            'headers' => [
                'Authorization' => 'authtoken {{AuthToken}}',
                'X-clientID' => "{{ClientID}}",
            ],
        ],
    ];

}
 
```
That should be it. Nothing else is required. All the API endpoint will be intelligently converted to functions of this 
class. For example, if you have an endpoint `[GET]/products`, you will get a `getProducts` function. Here are some more 
examples:

| HTTP Method | Endpoint | Converted function name | Parameters | Example call |
|-------------|----------|-------------------------|------------|--------------|
|GET| /products| `getProducts` | none | `$api->getProducts()`|  
|GET| /products/{product_id}| `getProduct` | `product_id` | `$api->getProduct(['product_id' => 101])`|  
|POST| /products| `addProduct` | a list of params  | `$api->addProduct($data)`|
|PUT| /products/{product_id}| updateProduct | `product_id` and a list of other parameters | `$api->updateProduct(['product_id' => 101])`...|
|DELETE| /products/{product_id}| deleteProduct | `product_id` | `$api->deleteProduct(['product_id' => 101])`|

Tried best to normalize the singulars and plurals.


> There can be cases when the API endpoint structure is same as the another but the data passed to it determines a 
different action altogether. Ideally, this should not happen but not all API enpoints are ideally created.
So, an endpoint like `[get]/products?filter_by=active` along with another endpoint `[get]/products` will have the 
same name. To keep both, the default behaviour is to add an `_` before the duplicate name. However, you can add filters 
or hooks to completely change the duplicate name to something else.  


<a name="install"/>

## Installation

<a name="requirements"/>

### Requirements

- Any flavour of PHP 7.0+ should do

<a name="install-composer"/>

### Install With Composer

You can install the library via [Composer](http://getcomposer.org) by adding the following line to the 
**require** block of your *composer.json* file (replace dev-master with latest stable version):

```
"sudiptochoudhury/php-api-client-forge": "dev-master"
```

or run the following command:

```
composer require sudiptochoudhury/php-api-client-forge
```

<a name="import"/>

## Importing Postman JSON

Export your Postman Collection using COllecton v2.1 format. It will be exported as JSON. Give the file a 
name, say `postman.json` and keep it inside a folder in your project. 

Next, extend `Import` class (recommended) or may use it directly.

#### By Extending the `Import` class
  	 
```php
<?php
namespace My\Namespace\ApiProvider;

use SudiptoChoudhury\Support\Forge\Api\Import as ParentImport;

/**
 * Class Import
 *
 * @package Pour\Package\Name
 */
class Import extends ParentImport
{

    protected $DEFAULT_API_JSON_PATH = './config/GuzzleDescription.json';
    protected $DEFAULT_SOURCE_JSON_PATH = './config/postman.json';
    
}
```
You are ready to import. That's pretty much it (though there are tons of configurable options and hooks...
 we will come to that soon).

Now, let's import in some other part of your codebase.

```php
<?php

use My\Namespace\ApiProvider\Import;
...
new (Import())->writeDefinition();
...

```

Done!


#### Directly using parent class

It's even simpler (though)

```php
<?php

use SudiptoChoudhury\Support\Forge\Api\Import;

...
new (Import([
   './config/postman.json'
]))->writeDefinition( './config/GuzzleDescription.json');
...
```

Simpler, eh!

You will find a new `.json`, a new `.md` and a new `.php` file to server you well in your API venture forward.

The `.json` file is obviously based on data structure required to create a `GuzzleHttp\Command\Guzzle\Description` 
object. 

The `.php` file has phpDoc `@method` definitions and may look something similar to 

```php
<?php
/** 
 * @method	array	getHostedpage(array $parameters)	Getting the details of a particular hosted page
 * @method	array	getHostedpages()	Listing all the hostedpages

```
 You can copy the method definitions parts to yor API class extended from the `Client` class.
 
 The `.md` file is a markdown file with a table containing details of the API functions, equivalent endpoints, 
 parameters, default values for parameters (if defined) and description - all derived from the Postman json file.
   
  

> However, I would recommend to use the extend option as you will get a plethora of options to hook into various 
sections of parent code while the conversion of the Postman data to Guzzle Description is undergoing. 
You may want to add default values or change a name or change description or skip some items. 
All can be done by overriding functions of Import parent class and defining custom filter functions.
    

<a name="filters"/>

## What are custom filter function?!!!

Well, let's pick an example. Let's say you see you want to change the duplicate function name for `[get]/products` as 
I stated an example in previous section. By default, the duplicate name will be set to `_getProducts` 
(remember? by default adding a `_` before the name). 

Now, in your extended `Import` class just define this

```php
<?php

		/** This is a filter 
		* @param $apiFunctionName
		* @param array $helperData
	 	* @return string
	 */
    public function filterFinalName($apiFunctionName, $helperData = [])
    {
        if ($apiFunctionName === '_getProducts') {
            $apiFunctionName = 'getActiveProducts';
        }
        return $apiFunctionName;
    }

```

This is a filter function. The name must start with `filter` followed by the filter name and can then optionally 
add an `_` and anything you want after that, just in case you want to define multiple functions for the same filter 
(like - `filterFinalName_xxx` or `filterFinalName_001`).

Each filter function will receive the value to be modified in the first parameter. The optional second parameter 
will be an associative array of helper data items that may help in determining logic of how the filter can be applied.
At times it may also be the source of data to build documentation layouts. For example, the markdown API table is 
generated via a default filter. You can override it to generate the API table in your own way. May be you do not like 
table structure, you may devise you own structure. Sky is the limit. Additionally, there are a lot of filters dedicated
to create a complete markdown documentation with sections like title, menu, description, table, header, footer, 
install, even donate and contact. Just tap into the desired layout filters and fill in your own content.


The table below gives you details of all the filters.

> But before that, you can also do similar things by totally overriding the parent's functions. For example, 
`readApiName`  can be overridden to extend or replace existing logic to detect a function name with your own logic.
However, filters help in changing things in a granular way. There can be 10 filters applied to a single 
function you wan to override and you may need to just use one filter out of them instead of writing a big bunch of 
overriding code. Of course, choice is yours.

| Filter Name | Description |
|-------------|------------- |   
| Name  | API function name of a single Endpoint |
| Slug  | A slug derived from API's name defined in Postmam  |
| FinalName  | API function name after all logic has been applied  |
| Params  |  Api function parameters |
| Uri  | Endpoint  |
| Operation  | Opetaion data of a single endpoint for Guzzle Description  |
| Title  | Title of the Postman collection  |
| DocMethodFinalText  | phpDoc method as joined text  |
| DocMDItem  | Markdown API for single endpoint details as array  |
| DocMDApiExternalLink  | Markdown's placeholder for link to external (API service provider's) API Documentation  |
| DocMDEndpoint  | Endpoint of API item in Markdown documentation |
| DocMDDescription  |  Description of API item in Markdown documentation  |
| DocMDParams  |  Parameters of API Item Markdown documentation |
| DocMDParamDefaults  | Default values for parameters of API item Markdown documentation |
| DocMDLayoutTitle  | Markdown documentation title  |
| DocMDLayoutSubtitle  | Markdown documentation subtitle  |
| DocMDLayoutHeader  |  Markdown documentation header |
| DocMDLayoutMenu  | Markdown documentation menu  |
| DocMDLayoutDescription  |  Markdown documentation description |
| DocMDLayoutRequirements  | Markdown documentation  requirements section |
| DocMDLayoutInstall  | Markdown documentation install section  |
| DocMDLayoutGetStarted  | Markdown documentation  gettting started section |
| DocMDLayoutSetup  | Markdown documentation  setup section |
| DocMDLayoutExamples  | Markdown documentation examples section  |
| DocMDLayoutTable  |  Markdown documentation API table section - helper data can be iterated to get data in this structure -`[ 'method' => '\<function name\>', 'endpoint' => '\<endpoint uri\>', 'parameters' => [`\<indexed array\>`], 'defaults' => ['<associative array>'], 'description' => '<api description>']`  |
| DocMDLayoutFooter  | Markdown documentation  footer section |
| DocMDLayoutNotes  |  Markdown documentation notes section |
| DocMDLayoutReferences  | Markdown documentation references section  |
| DocMDLayoutContact  |  Markdown documentation contact us section |
| DocMDLayoutDonate  | Markdown documentation  donate section |
| DocMDFinalLayoutArray  | All Markdown documentation layouts as array  |
| DocMDFinalText  |  Full Markdown documentation as text |
| APIDefinitionFinalJson  | The full Guzzle Description   |
| DocMethodItem  |  phpDoc method for single endpoint |
| DocMethodDescription  | Markdown documentation   |
| DocMethodFinalArray  | All phpDoc methods as array  |
| DocMethodParams  | Parameters for phpDoc  |
| DocMethodData  | What needs to shown in the parenthesis of the method signature in phpDoc |
| DocMethodSignature  | Full single method signature for phpDoc |
| GroupName  |  Postman Collection's Group Name |
| GroupDescription  | Post Collection's Description  |

