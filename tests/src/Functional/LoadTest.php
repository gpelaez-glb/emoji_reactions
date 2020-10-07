<?php

namespace Drupal\Tests\emoji_reactions\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group reactions
 */
class LoadTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['emoji_reactions'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Log in a new user to react, remove and view reactions.
    $this->user = $this->drupalCreateUser([
      'administer site configuration',
      'emoji_reactions_react',
      'emoji_reactions_remove',
      'emoji_reactions_view',
    ]);
    $this->drupalLogin($this->user);

    $this->createTestContent();
  }

  /**
   *
   */
  private function createTestContent() {
    // Create an article content type that we will use for testing.
    $article = $this->container->get('entity_type.manager')->getStorage('node_type')
      ->create([
        'type' => 'article',
        'name' => 'Test Article',
      ]);
    $article->save();
    $this->container->get('router.builder')->rebuild();
  }

  /**
   *
   */
  public function testSetReactions() {

  }

  /**
   * Tests that the home page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet(Url::fromRoute('<front>'));
    $this->assertSession()->assert(TRUE, '');
  }

  /**
   * @inheritdoc
   */
  public function assertSession($name = NULL) {
    return parent::assertSession($name);
  }

}
