<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251018123334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create initial database schema for Help My Dog: user, dog, advice_card, problem_category with full constraints and indexes';
    }

    public function up(Schema $schema): void
    {
        // Enable UUID extension for PostgreSQL
        $this->addSql('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');

        // ========================================
        // 1. CREATE TABLE: "user"
        // ========================================
        // Stores user accounts for JWT authentication (stateless)
        // Note: "user" is a reserved keyword in PostgreSQL, so we escape it with double quotes
        $this->addSql('
            CREATE TABLE "user" (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                email VARCHAR(255) NOT NULL,
                password_hash VARCHAR(255) NOT NULL,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                -- Constraints
                CONSTRAINT check_user_email_format CHECK (email ~* \'^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$\'),
                CONSTRAINT check_user_password_hash_length CHECK (LENGTH(password_hash) >= 60)
            )
        ');

        // Create unique index on email for authentication
        $this->addSql('CREATE UNIQUE INDEX idx_user_email ON "user"(email)');

        // ========================================
        // 2. CREATE TABLE: problem_category
        // ========================================
        // Lookup table for training problem categories
        $this->addSql('
            CREATE TABLE problem_category (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                code VARCHAR(50) NOT NULL,
                name VARCHAR(100) NOT NULL,
                priority INTEGER NOT NULL DEFAULT 0,
                is_active BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                -- Constraints
                CONSTRAINT check_problem_category_code_format CHECK (code ~ \'^[a-z_]+$\'),
                CONSTRAINT check_problem_category_name_length CHECK (LENGTH(name) >= 1 AND LENGTH(name) <= 100)
            )
        ');

        // Create unique index on code
        $this->addSql('CREATE UNIQUE INDEX idx_problem_category_code ON problem_category(code)');
        // Create index for sorting by priority
        $this->addSql('CREATE INDEX idx_problem_category_priority ON problem_category(priority)');

        // ========================================
        // 3. CREATE TABLE: dog
        // ========================================
        // Stores dog profiles for AI advice personalization
        $this->addSql('
            CREATE TABLE dog (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                user_id UUID NOT NULL,
                name VARCHAR(100) NOT NULL,
                breed VARCHAR(100) NOT NULL,
                age_months INTEGER NOT NULL,
                gender VARCHAR(10) NOT NULL,
                weight_kg DECIMAL(5,2) NOT NULL,
                energy_level VARCHAR(20) NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                -- Constraints
                CONSTRAINT check_dog_name_length CHECK (LENGTH(name) >= 1 AND LENGTH(name) <= 100),
                CONSTRAINT check_dog_age_months CHECK (age_months >= 0 AND age_months <= 300),
                CONSTRAINT check_dog_weight_kg CHECK (weight_kg > 0 AND weight_kg <= 200.00),
                CONSTRAINT check_dog_gender CHECK (gender IN (\'male\', \'female\')),
                CONSTRAINT check_dog_energy_level CHECK (energy_level IN (\'very_low\', \'low\', \'medium\', \'high\', \'very_high\'))
            )
        ');

        // Add foreign key to user with CASCADE delete
        $this->addSql('
            ALTER TABLE dog
            ADD CONSTRAINT fk_dog_user_id
            FOREIGN KEY (user_id) REFERENCES "user"(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ');

        // Create indexes for dog table
        $this->addSql('CREATE INDEX idx_dog_user_id ON dog(user_id)');
        // Composite index for "show active dog of user"
        $this->addSql('CREATE INDEX idx_dog_user_active ON dog(user_id, deleted_at)');
        // Partial index for active dog only
        $this->addSql('CREATE INDEX idx_dog_active ON dog(user_id) WHERE deleted_at IS NULL');

        // ========================================
        // 4. CREATE TABLE: advice_card
        // ========================================
        // Stores AI-generated advice cards and training plans
        $this->addSql('
            CREATE TABLE advice_card (
                id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
                dog_id UUID NOT NULL,
                category_id UUID NOT NULL,
                problem_description TEXT NOT NULL,
                ai_response TEXT NOT NULL,
                plan_content JSONB NULL,
                advice_type VARCHAR(20) NOT NULL,
                rating VARCHAR(20) NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL,
                -- Constraints
                CONSTRAINT check_advice_card_advice_type CHECK (advice_type IN (\'quick\', \'plan_7_days\')),
                CONSTRAINT check_advice_card_rating CHECK (rating IS NULL OR rating IN (\'helpful\', \'not_helpful\')),
                -- Ensure plan_content is present for plan_7_days and absent for quick
                CONSTRAINT check_advice_card_plan_content_when_plan CHECK (
                    (advice_type = \'plan_7_days\' AND plan_content IS NOT NULL) OR
                    (advice_type = \'quick\' AND plan_content IS NULL)
                )
            )
        ');

        // Add foreign keys
        // ON DELETE CASCADE for dog - deleting a dog deletes all its advice cards
        $this->addSql('
            ALTER TABLE advice_card
            ADD CONSTRAINT fk_advice_card_dog_id
            FOREIGN KEY (dog_id) REFERENCES dog(id)
            ON DELETE CASCADE
            ON UPDATE CASCADE
        ');

        // ON DELETE RESTRICT for categories - prevent deletion of categories in use
        $this->addSql('
            ALTER TABLE advice_card
            ADD CONSTRAINT fk_advice_card_category_id
            FOREIGN KEY (category_id) REFERENCES problem_category(id)
            ON DELETE RESTRICT
            ON UPDATE CASCADE
        ');

        // Create indexes for advice_card table
        $this->addSql('CREATE INDEX idx_advice_card_dog_id ON advice_card(dog_id)');
        $this->addSql('CREATE INDEX idx_advice_card_category_id ON advice_card(category_id)');
        $this->addSql('CREATE INDEX idx_advice_card_type ON advice_card(advice_type)');
        // Partial index for rated advice only
        $this->addSql('CREATE INDEX idx_advice_card_rating ON advice_card(rating) WHERE rating IS NOT NULL');
        // Composite index for "advice history of dog, sorted by newest"
        $this->addSql('CREATE INDEX idx_advice_card_dog_date ON advice_card(dog_id, created_at DESC)');
        // Composite index for "dog advice with rating"
        $this->addSql('CREATE INDEX idx_advice_card_dog_rating ON advice_card(dog_id, rating)');
        // Partial index for active advice cards
        $this->addSql('CREATE INDEX idx_advice_card_active ON advice_card(dog_id) WHERE deleted_at IS NULL');

        // ========================================
        // 5. INSERT INITIAL DATA: problem_category
        // ========================================
        // 8 categories for MVP
        $this->addSql("
            INSERT INTO problem_category (id, code, name, priority, is_active, created_at, updated_at) VALUES
            (gen_random_uuid(), 'behavior', 'Zachowanie', 1, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'obedience', 'Posłuszeństwo', 2, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'tricks', 'Nauka sztuczek', 3, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'free_shaping', 'Free-shaping', 4, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'socialization', 'Socjalizacja', 5, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'anxiety', 'Lęki i fobie', 6, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'leash_walking', 'Chodzenie na smyczy', 7, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP),
            (gen_random_uuid(), 'other', 'Inne', 99, TRUE, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
    }

    public function down(Schema $schema): void
    {
        // Drop tables in reverse order (respect foreign key constraints)
        $this->addSql('DROP TABLE IF EXISTS advice_card CASCADE');
        $this->addSql('DROP TABLE IF EXISTS dog CASCADE');
        $this->addSql('DROP TABLE IF EXISTS problem_category CASCADE');
        $this->addSql('DROP TABLE IF EXISTS "user" CASCADE');

        // Drop UUID extension (optional - may be used by other schemas)
        // $this->addSql('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
}
