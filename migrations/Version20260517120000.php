<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260517120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add explanation type to word explanations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE word_explanation ADD explanation_type VARCHAR(50) NOT NULL DEFAULT 'CUSTOM_PROMPT'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE word_explanation DROP explanation_type');
    }
}
