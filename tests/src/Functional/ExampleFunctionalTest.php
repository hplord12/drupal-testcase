<?php

namespace Drupal\Tests\testing_example\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceTestTrait;
use Drupal\Tests\image\Kernel\ImageFieldCreationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use Drupal\file\Entity\File;

/**
 * Class ExampleFunctionalTest.
 *
 * You likely will want to see the various pages and forms navigated by this
 * test. To do so, run PHPUnit with the equivalent of:
 *
 *
 * @group testing_example
 * @group examples
 */
class ExampleFunctionalTest extends BrowserTestBase {

  use EntityReferenceTestTrait;
  use ImageFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'image',
    'taxonomy',
    'link',
  ];

  /**
   * Fixture user with administrative powers.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Fixture authenticated user with no permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authUser;

  /**
   * Test nodes.
   *
   * @var \Drupal\node\Entity\Node[]
   */
  protected $nodes = [];

  /**
   * {@inheritdoc}
   *
   * The setUp() method is run before every other test method, so commonalities
   * should go here.
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'view the administration theme',
      'administer permissions',
      'administer nodes',
      'administer content types',
    ]);

    // Create non admin user
    $this->authUser = $this->drupalCreateUser([], 'authuser');

    // We have to create a content type because testing uses the 'testing'
    // profile, which has no content types by default.
    // Although we could have visited admin pages and pushed buttons to create
    // the content type, there happens to be function we can use in this case.
    $this->drupalCreateContentType([
      'type' => 'article',
      'name' => 'Article',
    ]);

    // Setup vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Add tags and field_image to the article.
    $this->createEntityReferenceField(
      'node',
      'article',
      'field_tags',
      'Tags',
      'taxonomy_term',
      'default',
      [
        'target_bundles' => [
          'tags' => 'tags',
        ],
        'auto_create' => TRUE,
      ],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );
    $this->createImageField('field_image', 'article');

    //  Create link field.
    FieldStorageConfig::create([
      'field_name' => 'field_link',
      'entity_type' => 'node',
      'type' => 'link',
      'settings' => [],
      'cardinality' => 1,
    ])->save();

    $field_config = FieldConfig::create([
        'field_name' => 'field_link',
        'label' => 'Link',
        'entity_type' => 'node',
        'bundle' => 'article',
        'required' => FALSE,
        'settings' => [],
        'description' => '',
    ]);
    $field_config->save();


    // Create role content editor which has article crud operation permissions.
    $role = Role::create([
        'id' => 'content_editor',
        'name' => 'Contet Editor',
        'permissions' => [
          'create article content',
          'edit any article content',
          'delete any article content',
          'access content',
        ]
    ]);
    $role->save();

    $this->authUser->addRole($role->id());
    $this->authUser->save();
    // Cache clear.
    drupal_flush_all_caches();
  }

  /**
   * Demonstrate node creation via NodeCreationTrait::createNode.
   */
  public function testNewPageApiCreate() {
    $assert = $this->assertSession();
    $random = $this->getRandomGenerator();

    // Login as admin
    // $this->drupalLogin($this->adminUser);

    // Log in our normal user and navigate to the node.
    $this->drupalLogin($this->authUser);


    $nodeTitle = 'Test node for testNewPageApiCreate';

    // Create terms
    $term = Term::create([
        'vid' => 'tags',
        'name' => "Tag1 Random",
    ]);

    $term->save();
    $this->tags[] = $term;

    $values = [
      'type' => 'article',
      'uid' => ['target_id' => $this->adminUser->id()],
      'title' => $nodeTitle,
      'body' => [
        [
          'format' => filter_default_format($this->adminUser),
          'value' => 'Body of test node',
        ]],
    ];

    $values['field_tags'] = [
      ['target_id' => 1]
    ];


    $file = File::create([
        'uri' => 'vfs://' . $random->name() . '.png',
    ]);
    $file->setPermanent();
    $file->save();
    $this->files[] = $file;
    $values['field_image'] = ['target_id' => $file->id(), 'alt' => 'alt text'];
    $values['field_link'] = [
      'title' => 'Drupal',
      'uri' => 'https://drupal.org',
    ];
    $node = $this->drupalCreateNode($values);
    $node->save();
    $url = $node->toUrl();

    // Confirm page creation.
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Log as admin user and navigate to the node.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet($url);
    $assert->statusCodeEquals(200);

    // Look at the *page* title.
    $assert->titleEquals("{$nodeTitle} | Drupal");

    // Find the title of the node itself.
    $nodeTitleElement = $this->getSession()
      ->getPage()
      ->find('css', 'h1 span.field--name-title');
    $this->assertEquals($nodeTitleElement->getText(), $nodeTitle);
  }

}
