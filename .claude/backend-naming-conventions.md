# Naming Conventions for Code Generation

This document defines naming conventions that Claude Code should follow when generating code for this project.

## General Principles

- Follow PSR-12 coding standards
- Use descriptive, self-documenting names
- Prefer clarity over brevity
- Use camelCase for variables and methods
- Use PascalCase for classes

## Variables

### Entity Variables

When working with entities, use the full entity name in camelCase:

```php
// ✅ Good
$attributeValue
$attributeValueLog
$contractorPerson
$patternComponent

// ❌ Bad
$av
$log
$person
$component
```

### Collection Variables

Use plural form for collections:

```php
// ✅ Good
$attributeValues
$attributeValueLogs
$contractors
$patterns

// ❌ Bad
$attributeValueList
$logCollection
$contractorArray
```

### Repository Variables

Always suffix with `Repository`:

```php
// ✅ Good
$attributeValueRepository
$attributeValueLogRepository
$contractorRepository

// ❌ Bad
$repo
$attributeValueRepo
$repository
```

### Service Variables

Always suffix with `Service`:

```php
// ✅ Good
$attributeValueLogService
$logService
$settingService

// ❌ Bad
$avlService
$service
$attributeValueLogSrv
```

### DTO Variables

Always suffix with `DTO`:

```php
// ✅ Good
$orderDTO
$contractorDTO
$patternDTO

// ❌ Bad
$dto
$order
$orderData
```

### Query/Command Variables

Use descriptive names reflecting the action:

```php
// ✅ Good
$query
$command
$getListQuery
$updateCommand

// ❌ Bad
$q
$cmd
$listQuery
```

### ID Variables

Always suffix numeric identifiers with `Id`:

```php
// ✅ Good
$attributeValueId
$patternId
$userId
$contractorId

// ❌ Bad
$id (unless it's clear from context)
$attributeValueID
$pattern_id
```

### Request/Response Variables

```php
// ✅ Good
$requestParams
$responseData
$requestBody

// ❌ Bad
$params
$data
$body
```

## Methods

### Finder Methods in Repositories

```php
// ✅ Good
findById($id)
findByAttributeValueId($attributeValueId)
findByPattern($patternId)
findAll(?RequestParams $requestParams = null)

// ❌ Bad
getById($id)
findByAttributeValue($id)
get($id)
```

### Service Methods

Use verb + noun pattern:

```php
// ✅ Good
logAttributeValueChange()
logAttributeValueCreation()
logAttributeValueDeletion()
logAttributeValueUpdate()
serializeAttributeValue()
getLogsByAttributeValueId()
getLogsByPattern()

// ❌ Bad
log()
create()
delete()
serialize()
getLogs()
```

### Getters/Setters

Standard bean notation:

```php
// ✅ Good
getAttributeValueId()
setAttributeValueId(?int $attributeValueId)
getCreatedAt()
setCreatedAt(\DateTimeInterface $createdAt)

// ❌ Bad
attributeValueId()
withAttributeValueId($id)
createdAt()
```

### Boolean Methods

Use `is`, `has`, or `can` prefixes:

```php
// ✅ Good
isEnabled()
hasPermission()
canAccess()

// ❌ Bad
enabled()
permission()
checkAccess()
```

## Classes

### Entities

Use singular, descriptive nouns:

```php
// ✅ Good
AttributeValue
AttributeValueLog
ContractorPerson
Pattern

// ❌ Bad
AttributeValues
AVLog
Person
```

### Repositories

Entity name + `Repository`:

```php
// ✅ Good
AttributeValueRepository
AttributeValueLogRepository
ContractorRepository

// ❌ Bad
AttributeValueRepo
AttributeValuesRepository
```

### Services

Descriptive name + `Service`:

```php
// ✅ Good
AttributeValueLogService
LogService
SettingService

// ❌ Bad
AttributeValueLogSrv
AVLService
```

### Controllers

Entity/Resource name + `Controller`:

```php
// ✅ Good
AttributeValueLogController
OrderController
ContractorController

// ❌ Bad
AttributeValueLogsController
AVLController
```

### DTOs

Entity name + `DTO`:

```php
// ✅ Good
OrderDTO
ContractorDTO
PatternDTO

// ❌ Bad
OrderData
OrderRequest
```

### Queries/Commands

Action + `Query`/`Command`:

```php
// ✅ Good
GetListQuery
GetByIdQuery
CreateCommand
UpdateCommand
DeleteCommand

// ❌ Bad
ListQuery
IdQuery
Create
Update
```

### Query/Command Handlers

Query/Command name + `Handler`:

```php
// ✅ Good
GetListQueryHandler
GetByIdQueryHandler
CreateCommandHandler

// ❌ Bad
GetListHandler
ListQueryHandler
CreateHandler
```

## Constants

Use SCREAMING_SNAKE_CASE:

```php
// ✅ Good
const MAX_RETRY_ATTEMPTS = 3;
const DEFAULT_TIMEOUT = 30;
const ATTRIBUTE_TYPE_TEXT = 'text';

// ❌ Bad
const maxRetryAttempts = 3;
const DefaultTimeout = 30;
```

## Arrays/JSON Keys

Use snake_case for array keys and JSON properties:

```php
// ✅ Good
[
    'attribute_value_id' => 1,
    'created_at' => '2025-01-01',
    'old_value' => [],
]

// ❌ Bad
[
    'attributeValueId' => 1,
    'createdAt' => '2025-01-01',
    'OldValue' => [],
]
```

## Database-Related

### Table Names

Use snake_case, plural form:

```
attribute_values
attribute_value_logs
contractors
patterns
```

### Column Names

Use snake_case:

```
attribute_value_id
created_at
old_value
new_value
```

## File Names

Match the class name:

```
// ✅ Good
AttributeValueLog.php
AttributeValueLogRepository.php
AttributeValueLogService.php
GetListQuery.php
GetListQueryHandler.php

// ❌ Bad
attributeValueLog.php
AttributeValueLogRepo.php
GetList_Query.php
```

## Namespace Organization

Follow PSR-4 and project structure:

```php
// ✅ Good
namespace App\Entity;
namespace App\Repository;
namespace App\Service\AttributeValue;
namespace App\Action\Query\AttributeValueLog\GetList;
namespace App\Action\Command\Order\Create;

// ❌ Bad
namespace App\Entities;
namespace App\Repos;
namespace App\Services;
```

## Examples from Codebase

### Good Examples

```php
// Repository method
public function findByAttributeValueId(int $attributeValueId): array

// Service method
public function logAttributeValueChange(
    AttributeValue $attributeValue,
    ?array $oldValue = null,
    ?array $newValue = null,
    string $type = 'update'
): void

// Query handler
public function __invoke(GetListQuery $query): array
{
    return $this->repository->findAll($query->getRequestParams());
}

// Controller action
public function cgetAction(RequestParams $requestParams): array
{
    return $this->handle(new GetListQuery($requestParams));
}
```

## Special Cases

### Acronyms

Keep acronyms uppercase only when used alone, otherwise follow camelCase:

```php
// ✅ Good
$erpContractor
$httpClient
$apiKey
$xmlParser

// ❌ Bad
$ERPContractor
$HTTPClient
$APIKey
$XMLParser
```

### Abbreviations

Avoid abbreviations unless they are universally understood:

```php
// ✅ Good
$attributeValue
$identifier
$maximum
$minimum

// ❌ Bad (unless context is very clear)
$attrValue
$id (when not clearly an ID)
$max
$min
```

### Legacy Code

When working with existing code, match the existing naming style in that file/module. When creating new code, always use these conventions.
