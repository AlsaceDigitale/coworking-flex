<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181217133729 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, date DATE DEFAULT NULL, active TINYINT(1) NOT NULL, INDEX IDX_A3C664D39395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, lastname VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(255) NOT NULL, zip VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, mail VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_81398E095126AC48 (mail), UNIQUE INDEX UNIQ_81398E09F85E0677 (username), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE promo (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, counter INT NOT NULL, UNIQUE INDEX UNIQ_B0139AFB9395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE check_in (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, arrival DATETIME NOT NULL, leaving DATETIME DEFAULT NULL, diff TIME DEFAULT NULL, half_day INT DEFAULT NULL, free INT DEFAULT NULL, INDEX IDX_90466CF99395C3F3 (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE options (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, content TEXT, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE promo ADD CONSTRAINT FK_B0139AFB9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE check_in ADD CONSTRAINT FK_90466CF99395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D39395C3F3');
        $this->addSql('ALTER TABLE promo DROP FOREIGN KEY FK_B0139AFB9395C3F3');
        $this->addSql('ALTER TABLE check_in DROP FOREIGN KEY FK_90466CF99395C3F3');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE promo');
        $this->addSql('DROP TABLE check_in');
        $this->addSql('DROP TABLE options');
    }
}
