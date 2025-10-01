<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251001144439 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE clothe (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, current_borrower_id INT DEFAULT NULL, state_id INT DEFAULT NULL, category_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, img VARCHAR(255) DEFAULT NULL, INDEX IDX_C32115BAA76ED395 (user_id), INDEX IDX_C32115BAAF1A9AF2 (current_borrower_id), INDEX IDX_C32115BA5D83CC1 (state_id), INDEX IDX_C32115BA12469DE2 (category_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rent (id INT AUTO_INCREMENT NOT NULL, clothes_id INT DEFAULT NULL, user_id INT DEFAULT NULL, date_debut DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, INDEX IDX_2784DCC271E85C0 (clothes_id), INDEX IDX_2784DCCA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE state (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(50) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE clothe ADD CONSTRAINT FK_C32115BAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE clothe ADD CONSTRAINT FK_C32115BAAF1A9AF2 FOREIGN KEY (current_borrower_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE clothe ADD CONSTRAINT FK_C32115BA5D83CC1 FOREIGN KEY (state_id) REFERENCES state (id)');
        $this->addSql('ALTER TABLE clothe ADD CONSTRAINT FK_C32115BA12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE rent ADD CONSTRAINT FK_2784DCC271E85C0 FOREIGN KEY (clothes_id) REFERENCES clothe (id)');
        $this->addSql('ALTER TABLE rent ADD CONSTRAINT FK_2784DCCA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE clothe DROP FOREIGN KEY FK_C32115BAA76ED395');
        $this->addSql('ALTER TABLE clothe DROP FOREIGN KEY FK_C32115BAAF1A9AF2');
        $this->addSql('ALTER TABLE clothe DROP FOREIGN KEY FK_C32115BA5D83CC1');
        $this->addSql('ALTER TABLE clothe DROP FOREIGN KEY FK_C32115BA12469DE2');
        $this->addSql('ALTER TABLE rent DROP FOREIGN KEY FK_2784DCC271E85C0');
        $this->addSql('ALTER TABLE rent DROP FOREIGN KEY FK_2784DCCA76ED395');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE clothe');
        $this->addSql('DROP TABLE rent');
        $this->addSql('DROP TABLE state');
    }
}
