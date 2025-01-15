<?php

namespace Drupal\Tests\taxonomy\Functional;

use Drupal\taxonomy\TermInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests the revisionability of taxonomy_term entities.
 *
 * @group taxonomy
 */
class TaxonomyTermRevisionTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Vocabulary for testing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Checks taxonomy term revision operations.
   */
  public function testRevisions() {
    $assert = $this->assertSession();

    $this->vocabulary = $this->createVocabulary();

    // Create a taxonomy term.
    $taxonomy_term = $this->createTerm($this->vocabulary);

    // Test permissions.
    $account = $this->drupalCreateUser(['administer taxonomy']);
    $this->drupalLogin($account);

    // You as an administrator user can access the revision page
    // when there is only 1 revision.
    $this->drupalGet('taxonomy/' . $taxonomy_term->id() . '/revisions/' . $taxonomy_term->getRevisionId() . '/view');
    $assert->statusCodeEquals(200);

    // Create some revisions.
    $taxonomy_term_revisions = [];
    $taxonomy_term_revisions[] = clone $taxonomy_term;
    $revision_count = 3;
    for ($i = 0; $i < $revision_count; $i++) {
      $taxonomy_term->revision_log = $this->randomMachineName(32);
      $taxonomy_term = $this->createTaxonomyTermRevision($taxonomy_term);
      $taxonomy_term_revisions[] = clone $taxonomy_term;
    }

    // Get the last revision for simple checks.
    /** @var \Drupal\taxonomy\TermInterface $taxonomy_term */
    $taxonomy_term = end($taxonomy_term_revisions);

    // Test permissions.
    $this->drupalLogin($this->drupalCreateUser([]));
    /** @var \Drupal\user\RoleInterface $role */
    $role = Role::load(RoleInterface::AUTHENTICATED_ID);

    // Test 'view all taxonomy revisions' permission
    // ('access content' permission is needed as well).
    user_role_revoke_permissions($role->id(), [
      'access content',
      'view all taxonomy revisions',
    ]);
    $this->drupalGet('taxonomy/' . $taxonomy_term->id() . '/revisions/' . $taxonomy_term->getRevisionId() . '/view');
    $assert->statusCodeEquals(403);
    $this->grantPermissions($role, [
      'access content',
      'view all taxonomy revisions',
    ]);
    $this->drupalGet('taxonomy/' . $taxonomy_term->id() . '/revisions/' . $taxonomy_term->getRevisionId() . '/view');
    $assert->statusCodeEquals(200);

    // Confirm the revision page shows the correct title.
    $assert->pageTextContains($taxonomy_term->getName());

    // Confirm that the last revision is the default revision.
    $this->assertTrue($taxonomy_term->isDefaultRevision(), 'Last revision is the default.');
  }

  /**
   * Creates a new revision for a given taxonomy_term item.
   *
   * @param \Drupal\taxonomy\TermInterface $taxonomy_term
   *   A taxonomy_term object.
   *
   * @return \Drupal\taxonomy\TermInterface
   *   A taxonomy_term object with up to date revision information.
   */
  protected function createTaxonomyTermRevision(TermInterface $taxonomy_term) {
    $taxonomy_term->setName($this->randomMachineName());
    $taxonomy_term->setNewRevision();
    $taxonomy_term->save();
    return $taxonomy_term;
  }

}
