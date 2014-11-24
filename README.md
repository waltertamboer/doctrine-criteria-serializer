# doctrine-criteria-serializer

[![Build Status](https://travis-ci.org/waltertamboer/doctrine-criteria-serializer.svg?branch=master)](https://travis-ci.org/waltertamboer/doctrine-criteria-serializer)

A small PHP library that can be used to serialize a Doctrine Criteria object.

## Example

To serialize a criteria, simply call `CriteriaSerializer::serialize`.

```php
<?php

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\CriteriaSerializer;

$criteria = Criteria::create();
$criteria->where($criteria->expr()->contains('field2', '2'));

$serializer = new CriteriaSerializer();
$serializedString = $serializer->serialize($criteria);
```

To get a Criteria object again, simply call `CriteriaSerializer::unserialize`.

```php
<?php

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\CriteriaSerializer;

$serializer = new CriteriaSerializer();
$criteria = $serializer->unserialize($data);
```
