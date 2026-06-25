# holonet/common

Commonly used code in holonet projects: a PSR-11 dependency injection container with
compile-time optimisation, dot-key configuration registries, attribute based object
validation, change aware collections, error handling and assorted utility helpers.

Requires PHP >= 8.4.

## Installation

```sh
composer require holonet/common
```

## Components

### Dependency injection (`holonet\common\di`)

A PSR-11 container with reflection based auto-wiring:

```php
use holonet\common\di\Container;

$container = new Container();

// register a shared service (by instance or by class name)
$container->set('logger', new Monolog\Logger('app'));
$container->set('mailer', MySmtpMailer::class, ['port' => 2525]);

// map an interface to a concrete implementation
$container->wire(MySmtpMailer::class, name: MailerInterface::class);

// constructor parameters are resolved by type, service name hint or config
$service = $container->instance(NeedsAMailer::class);
```

Constructor parameters can be filled from the configuration registry with the
`#[ConfigItem]` attribute, optionally verified against the rules on the config DTO:

```php
use holonet\common\di\autowire\attribute\ConfigItem;

class Database {
	public function __construct(#[ConfigItem('database')] DbConfig $config) {
	}
}
```

The `Factory` bootstraps a container from configuration (`di.services` / `di.auto_wire`).
When `di.cache_path` is configured, the container is compiled into a plain PHP class that
instantiates all known services without any reflection:

```php
use holonet\common\di\Factory;

$factory = new Factory($registry);
$container = $factory->make();
```

### Configuration registry (`holonet\common\collection`)

`Registry` is a dot-key addressable key-value store with `%placeholder%` resolution;
`ConfigRegistry` adds `%env(VAR)%` environment lookups and config-to-DTO mapping:

```php
$registry = new ConfigRegistry();
$registry->set('app.name', 'coolapp');
$registry->set('app.cache_dir', '%env(CACHE_DIR)%/%app.name%');

$registry->get('app.cache_dir');     // '/tmp/cache/coolapp'
$registry->asDto('database', DbConfig::class);       // hydrate + type check
$registry->verifiedDto('database', DbConfig::class); // hydrate + run verifier rules
```

### Verifier (`holonet\common\verifier`)

Attribute based validation of plain PHP objects. Rules live on public properties,
`verify()` returns a `Proof` holding all error messages:

```php
use holonet\common\verifier\rules\Required;
use holonet\common\verifier\rules\string\MinLength;
use function holonet\common\verify;

class SignUp {
	#[Required]
	#[MinLength(3, message: ':attr is too short')]
	public string $username;
}

$proof = verify(new SignUp());
$proof->pass();           // bool
$proof->attr('username'); // error messages for one property
```

Rule namespaces: `rules` (Required, Url, InArray), `rules\string`, `rules\numeric`,
`rules\filesystem` and `rules\class`. Custom logic can be added per object via the
`VerifiesState` interface, custom rules by extending `Rule` and implementing
`CheckValueRuleInterface` and/or `TransformValueRuleInterface`.

### Collections (`holonet\common\collection`)

- `Collection`: array/object style wrapper around a data array (`ArrayAccess`,
  `Countable`, `IteratorAggregate`).
- `ChangeAwareCollection`: tracks added / changed / removed entries until `apply()`
  commits them (`getAll('new')`, `getAll('changed')`, ...). Values implementing
  `ChangeAwareInterface` (see `ChangeAwareTrait`) can notify the collection themselves.

### Error handling (`holonet\common\error`)

- `ErrorHandler`: maps PHP errors to PSR-3 log levels and logs uncaught exceptions and
  fatal errors to any `Psr\Log\LoggerInterface` (or STDERR without one). `register()`
  installs the error, exception and shutdown hooks.
- `ErrorDispatcher`: fans error / exception / shutdown events out to any number of
  registered callbacks.

### Class discovery (`holonet\common\discovery`)

Discover fully qualified class names from source files, either by tokenising the file
(`TokeniserClassDiscovery`) or from the PSR-4 directory layout (`Psr4ClassDiscovery`).

### Helper functions (`holonet\common`)

`src/functions.php` provides, among others:

- `dot_key_get/set/flatten/array_merge()`: dot-notation access into nested arrays/objects
- `verify()`: shorthand for running the verifier
- `read_php_config_file()`: require a config file and validate its return value
- `reflection_get_attribute(s)()`: typed attribute lookup on reflection objects
- `stringify()`, `indentText()`, `readableDurationString()`, `str_lreplace()`,
  `get_absolute_path()`, `get_class_short()`, `raise()`

`src/kvm_functions.php` implements a small serialisation format for key/value-or-list
metadata (`kvm_serialise()`, `kvm_parse()`, `kvm_match()` querying, `kvm_walk_pair()`).

## Development

```sh
composer test   # validate, phpunit (with coverage), php-cs-fixer dry run, psalm
composer fix    # composer normalize + php-cs-fixer
```
