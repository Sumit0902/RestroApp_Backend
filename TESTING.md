# Testing Documentation

## Overview

This document provides an overview of the testing strategy used in the project. It includes details on the types of tests, the number of tests, and the purpose of each test.

## Test Directory Structure

The tests are organized into two main directories:

-   `Unit`: Contains unit tests that test individual components of the application.
-   `Feature`: Contains feature tests that test the application from the user's perspective.

## Unit Tests

Unit tests are used to test individual components of the application in isolation. These tests are located in the `tests/Unit` directory.

### List of Unit Tests

1. **HelloWorldTest**

    - **File**: `tests/Unit/HelloWorldTest.php`
    - **Purpose**: To test a simple "Hello, World!" output.
    - **Test Method**: `testHelloWorld`

2. **ExampleTest**

    - **File**: `tests/Unit/ExampleTest.php`
    - **Purpose**: To test a basic example.
    - **Test Method**: `test_that_true_is_true`

3. **DepartmentTest**

    - **File**: `tests/Unit/DepartmentTest.php`
    - **Purpose**: To test the relationship between departments and employees.
    - **Test Methods**: `testDepartmentHasEmployees`

4. **CompanyTest**
    - **File**: `tests/Unit/CompanyTest.php`
    - **Purpose**: To test the relationships within a company.
    - **Test Methods**: `testCompanyHasEmployees`, `testCompanyHasShifts`

## Feature Tests

Feature tests are used to test the application from the user's perspective. These tests are located in the `tests/Feature` directory.

### List of Feature Tests

1. **UserControllerTest**

    - **File**: `tests/Feature/UserControllerTest.php`
    - **Purpose**: To test user-related API endpoints.
    - **Test Methods**: `testStoreUser`, `testShowUser`

2. **ScheduleControllerTest**

    - **File**: `tests/Feature/ScheduleControllerTest.php`
    - **Purpose**: To test schedule-related API endpoints.
    - **Test Methods**: `testStoreSchedule`, `testShowSchedule`

3. **NotificationControllerTest**

    - **File**: `tests/Feature/NotificationControllerTest.php`
    - **Purpose**: To test notification-related API endpoints.
    - **Test Methods**: `testStoreNotification`, `testMarkAsRead`, `testGetUserNotifications`

4. **ExampleTest**
    - **File**: `tests/Feature/ExampleTest.php`
    - **Purpose**: To test a basic example.
    - **Test Method**: `test_the_application_returns_a_successful_response`

## Summary

In total, there are 7 unit tests and 8 feature tests. These tests ensure that the individual components of the application work correctly and that the application behaves as expected from the user's perspective.

Unit tests are used to verify the functionality of individual components in isolation, while feature tests are used to verify the overall behavior of the application. By using both types of tests, we can ensure that our application is robust and reliable.
