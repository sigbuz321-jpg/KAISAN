-- Runs only when the postgres volume is created for the first time.
-- Tests run against PostgreSQL, not SQLite: the schema in docs/03-DATABASE.md
-- leans on JSONB, window functions and CHECK constraints that SQLite cannot model.
CREATE DATABASE kaisan_test OWNER kaisan;
