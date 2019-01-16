<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190104092515 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE options ADD active TINYINT(1) DEFAULT NULL');
        $this->addSql("INSERT INTO options(label, content, active) 
                           VALUES ('Place', 18, null ), 
                                  ('Text', 'Votre texte ici.' , 0), 
                                  ('HalfDay', 6, null ), 
                                  ('Month', 140, null )");
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE options DROP active, CHANGE content content VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci');
    }
}