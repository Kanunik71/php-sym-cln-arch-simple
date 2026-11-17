-- Выполняется только при первой инициализации тома postgres_data.
-- Основная БД app создаётся образом через POSTGRES_DB; здесь — отдельная БД для тестов.
CREATE DATABASE app_test OWNER app;
