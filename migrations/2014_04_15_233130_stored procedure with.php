<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class StoredProcedureWith extends Migration
{
    /**
     * Usage: the standard syntax:
     * WITH RECURSIVE recursive_table AS
     * (initial_SELECT
     *  UNION ALL
     *  recursive_SELECT)
     *  final_SELECT;
     * should be translated by you to
     * CALL WITH_EMULATOR(recursive_table, initial_SELECT, recursive_SELECT, final_SELECT, 0, "").
     *
     * ALGORITHM:
     * 1) we have an initial table T0 (actual name is an argument "recursive_table"), we fill it with result of initial_SELECT.
     * 2) We have a union table U, initially empty.
     * 3) Loop:
     *      add rows of T0 to U,
     *      run recursive_SELECT based on T0 and put result into table T1,
     *      if T1 is empty
     *          leave loop
     *      else
     *          swap T0 and T1 (renaming) and empty T1
     * 4) Drop T0, T1
     * 5) Rename U to T0
     * 6) run final select, send relult to client
     *
     * This is for *one* recursive table.
     * It would be possible to write a SP creating multiple recursive tables.
     *
     * Largely inspired from :: http://guilhembichot.blogspot.co.uk/2013/11/with-recursive-and-mysql.html
     *
     * @var string
     */
    protected static $PROCEDURE = <<<SQL
DROP PROCEDURE IF EXISTS WITH_EMULATOR;
CREATE PROCEDURE WITH_EMULATOR(
  recursive_table      VARCHAR(100),   # name of recursive table
  initial_SELECT       VARCHAR(21845), # seed a.k.a. anchor
  recursive_SELECT     VARCHAR(21845), # recursive member
  final_SELECT         VARCHAR(21845), # final SELECT on UNION result
  max_recursion        INT UNSIGNED,   # safety against infinite loop, use 0 for default
  create_table_options VARCHAR(21845)  # you can add CREATE-TABLE-time options to your recursive_table, to speed up initial/recursive/final SELECTs; example: "(KEY(some_column)) ENGINE=MEMORY"
)

BEGIN
  DECLARE new_rows INT UNSIGNED;
  DECLARE recursive_table_next VARCHAR(120);
  DECLARE recursive_table_union VARCHAR(120);
  DECLARE recursive_table_tmp VARCHAR(120);
  SET recursive_table_next = concat(recursive_table, "_next");
  SET recursive_table_union = concat(recursive_table, "_union");
  SET recursive_table_tmp = concat(recursive_table, "_tmp");

  # create and fill T0
  # If you need to reference recursive_table more than once in recursive_SELECT, remove the TEMPORARY word.
  SET @str = CONCAT("CREATE TEMPORARY TABLE ", recursive_table, " ", create_table_options, " AS ", initial_SELECT);
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  # create U
  SET @str = CONCAT("CREATE TEMPORARY TABLE ", recursive_table_union, " LIKE ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  # create T1
  SET @str = CONCAT("CREATE TEMPORARY TABLE ", recursive_table_next, " LIKE ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  IF max_recursion = 0
  THEN
    SET max_recursion = 100; # a default to protect the innocent
  END IF;
  recursion: REPEAT
    # add T0 to U (this is always UNION ALL)
    SET @str = CONCAT("INSERT INTO ", recursive_table_union, " SELECT * FROM ", recursive_table);
    PREPARE stmt FROM @str;
    EXECUTE stmt;

    # we are done if max depth reached
    SET max_recursion = max_recursion - 1;
    IF NOT max_recursion
    THEN
      LEAVE recursion;
    END IF;

    # fill T1 by applying the recursive SELECT on T0
    SET @str = CONCAT("INSERT INTO ", recursive_table_next, " ", recursive_SELECT);
    PREPARE stmt FROM @str;
    EXECUTE stmt;

    # we are done if no rows in T1
    SELECT row_count() INTO new_rows;
    IF NOT new_rows
    THEN
      LEAVE recursion;
    END IF;

    # Prepare next iteration:
    # T1 becomes T0, to be the source of next run of recursive_SELECT,
    # T0 is recycled to be T1.
    SET @str = CONCAT("ALTER TABLE ", recursive_table, " RENAME ", recursive_table_tmp);
    PREPARE stmt FROM @str;
    EXECUTE stmt;

    # we use ALTER TABLE RENAME because RENAME TABLE does not support temp tables
    SET @str = CONCAT("ALTER TABLE ", recursive_table_next, " RENAME ", recursive_table);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
    SET @str = CONCAT("ALTER TABLE ", recursive_table_tmp, " RENAME ", recursive_table_next);
    PREPARE stmt FROM @str;
    EXECUTE stmt;

    # empty T1
    SET @str = CONCAT("TRUNCATE TABLE ", recursive_table_next);
    PREPARE stmt FROM @str;
    EXECUTE stmt;
  UNTIL 0 END REPEAT;

  # eliminate T0
  SET @str = CONCAT("DROP TEMPORARY TABLE ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  # Final (output) SELECT uses recursive_table name
  SET @str = CONCAT("ALTER TABLE ", recursive_table_union, " RENAME ", recursive_table);
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  # Run final SELECT on UNION
  SET @str = final_SELECT;
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  # Remove old temporary tables
  SET @str = CONCAT("DROP TEMPORARY TABLE IF EXISTS ", recursive_table, ", ", recursive_table_next, ", ", recursive_table_tmp);
  PREPARE stmt FROM @str;
  EXECUTE stmt;

  # We are done :-)
END;
SQL;

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (DB::connection()->getDriverName() != 'mysql') {
            return;
        }

        DB::connection()->getPdo()->exec(self::$PROCEDURE);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (DB::connection()->getDriverName() != 'mysql') {
            return;
        }
        DB::connection()->getPdo()->exec("DROP PROCEDURE IF EXISTS WITH_EMULATOR");
    }
}
