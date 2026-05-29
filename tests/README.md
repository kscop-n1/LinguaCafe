# LinguaCafe Test Suite Overview (`tests/`)

This directory houses the backend testing suite powered by PHPUnit.

---

## 1. Directory Structure

* [TestCase.php](file:///c:/q/git/linguacafe/LinguaCafe/tests/TestCase.php): Base class for setting up test instances.
* **`Feature/`**: Integration tests testing requests, session controls, and redirect flows.
  * **`Auth/`**:
    * [AuthenticationTest.php](file:///c:/q/git/linguacafe/LinguaCafe/tests/Feature/Auth/AuthenticationTest.php): Tests login validations, wrong password errors, and session logouts.
    * [RegistrationTest.php](file:///c:/q/git/linguacafe/LinguaCafe/tests/Feature/Auth/RegistrationTest.php): Validates user creation.
    * [PasswordResetTest.php](file:///c:/q/git/linguacafe/LinguaCafe/tests/Feature/Auth/PasswordResetTest.php): Validates token resets.
    * [EmailVerificationTest.php](file:///c:/q/git/linguacafe/LinguaCafe/tests/Feature/Auth/EmailVerificationTest.php): Validates auth email verify flows.
* **`Unit/`**: Contains core code tests that isolate and verify individual functions or class methods.

---

## 2. Command Line Operations

Tests are configured using the root `phpunit.xml` configuration which specifies array caching, in-memory sync queues, and testing database connections.
* **Execute all tests**:
  ```bash
  php artisan test
  ```
* **Filter a specific test case**:
  ```bash
  php artisan test --filter=AuthenticationTest
  ```
