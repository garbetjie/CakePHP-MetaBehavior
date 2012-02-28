CakePHP MetaBehavior
====================

### Introduction
CakePHP Metabehavior is a simple-to-use Behavior for CakePHP 2.0+. This behavior allows you to store and retrieve meta data about any record for any model within your database. This becomes really useful when you need to store unrelated and arbitrary data about various records.

### Installation
1. Copy the provided files into your CakePHP folder structure.

2. Run the following commands:
<pre>cd /path/to/installation/root
php app/Console/cake.php schema create meta</pre>

3. Add the behavior to whichever models you will make use of this behavior on (or AppModel for all your models).
<pre>class AppModel extends Model {
	public $actsAs = array('Meta');
}</pre>

### Usage
There are multiple ways of accessing data from this behavior:

1. You can find individual meta data items.
<pre>// ID *must* be populated.
$this->Model->id = 1;
$foo = $this->Model->meta('bar');
// or
$foo = $this->Model->meta(array('bar', 'baz'));
// or
$foo = $this->Model->meta('bar', 'baz');</pre>

2. You can save meta data items on the fly.
<pre>// ID *must* be populated
$this->Model->id = 1;
$this->Model->saveMeta('field', 'value');
// or
$this->Model->saveMeta(array('field' => 'value', 'foo' => 'bar'));</pre>

3. You can save meta data items as part of Model::save().
<pre>$this->Model->save(array(
	'Model' => array(
		'field' => 'value',
		'field2' => 'value2'
	),
	'MetaData' => array(
		'name' => 'value',
		'bar' => 'baz'
	)
));</pre>

4. You can retrieve meta data items as part of a normal find.
<pre>$results = $this->Model->find('first');
// Yields:
array(
	'Model' => array(
		'field' => 'value',
		'field2' => 'value2',
	),
	'MetaData' => array(
		'name' => 'value',
		'bar' => 'baz',
	),
);</pre>

### Bugs / Enhancements
Please report any bugs you find, or enhancements you think can make this behavior better.
