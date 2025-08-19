# Contributing to Laravel Memory Profiler

Thank you for considering contributing to the Laravel Memory Profiler package! This document provides guidelines and information for contributors.

## Code of Conduct

This project adheres to a code of conduct. By participating, you are expected to uphold this code. Please report unacceptable behavior to the project maintainers.

## How to Contribute

### Reporting Bugs

Before creating bug reports, please check the existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title and description**
- **Steps to reproduce** the issue
- **Expected behavior** vs **actual behavior**
- **Environment details** (PHP version, Laravel version, OS)
- **Code samples** or error messages
- **Screenshots** if applicable

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

- **Clear title and description** of the enhancement
- **Use case** explaining why this would be useful
- **Detailed explanation** of how it should work
- **Examples** of similar features in other tools

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Install dependencies**: `composer install`
3. **Make your changes** following the coding standards
4. **Add tests** for new functionality
5. **Run the test suite**: `composer test`
6. **Update documentation** if needed
7. **Create a pull request** with a clear description

## Development Setup

### Prerequisites

- PHP 8.0 or higher
- Composer
- Laravel 9.0+ (for testing)

### Installation

```bash
# Clone your fork
git clone https://github.com/yourusername/laravel-memory-profiler.git
cd laravel-memory-profiler

# Install dependencies
composer install

# Run tests
composer test
```

### Testing

```bash
# Run all tests
composer test

# Run specific test suite
./vendor/bin/phpunit tests/Unit
./vendor/bin/phpunit tests/Feature

# Run with coverage
./vendor/bin/phpunit --coverage-html coverage
```

## Coding Standards

### PHP Standards

- Follow **PSR-12** coding standard
- Use **strict types** declarations
- Add **type hints** for all parameters and return types
- Write **comprehensive docblocks**

### Code Style

```php
<?php

declare(strict_types=1);

namespace YourName\LaravelMemoryProfiler;

/**
 * Example class demonstrating coding standards.
 */
class ExampleClass
{
    /**
     * Example method with proper documentation.
     */
    public function exampleMethod(string $parameter): array
    {
        // Implementation
        return [];
    }
}
```

### Naming Conventions

- **Classes**: PascalCase (`MemoryProfiler`)
- **Methods**: camelCase (`collectSample`)
- **Variables**: camelCase (`$memoryUsage`)
- **Constants**: UPPER_SNAKE_CASE (`DEFAULT_INTERVAL`)

## Testing Guidelines

### Test Structure

- **Unit tests**: Test individual classes and methods
- **Feature tests**: Test complete functionality
- **Integration tests**: Test package integration with Laravel

### Test Naming

```php
/** @test */
public function it_can_track_memory_usage()
{
    // Test implementation
}

/** @test */
public function it_throws_exception_when_invalid_input()
{
    // Test implementation
}
```

### Test Coverage

- Aim for **80%+ code coverage**
- Test **happy paths** and **error conditions**
- Include **edge cases** and **boundary conditions**

## Documentation

### Code Documentation

- All public methods must have **docblocks**
- Include **parameter descriptions** and **return types**
- Add **usage examples** for complex methods
- Document **exceptions** that may be thrown

### User Documentation

- Update relevant **markdown files**
- Add **examples** for new features
- Update **configuration** documentation
- Include **troubleshooting** information

## Commit Guidelines

### Commit Messages

Use conventional commit format:

```
type(scope): description

[optional body]

[optional footer]
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat(tracker): add garbage collection monitoring
fix(profiler): resolve memory leak in sample collection
docs(readme): update installation instructions
test(unit): add tests for memory tracker
```

### Branch Naming

- `feature/description` for new features
- `fix/description` for bug fixes
- `docs/description` for documentation
- `refactor/description` for refactoring

## Release Process

### Version Numbers

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes (backward compatible)

### Release Checklist

- [ ] Update version in `composer.json`
- [ ] Update `CHANGELOG.md`
- [ ] Run full test suite
- [ ] Update documentation
- [ ] Create release tag
- [ ] Publish to Packagist

## Architecture Guidelines

### Package Structure

```
src/
├── Commands/          # Artisan commands
├── Trackers/          # Memory and database trackers
├── Reporters/         # Report generators
├── Exceptions/        # Custom exceptions
└── MemoryProfiler.php # Main profiler class
```

### Design Principles

- **Single Responsibility**: Each class has one clear purpose
- **Dependency Injection**: Use Laravel's container
- **Interface Segregation**: Small, focused interfaces
- **Open/Closed**: Open for extension, closed for modification

### Performance Considerations

- **Minimal Overhead**: Profiling should not significantly impact performance
- **Memory Efficiency**: Avoid memory leaks in the profiler itself
- **Configurable Sampling**: Allow users to balance accuracy vs performance

## Security Guidelines

### Security Considerations

- **File Permissions**: Ensure proper file access controls
- **Input Validation**: Validate all user inputs
- **Path Traversal**: Prevent directory traversal attacks
- **Information Disclosure**: Avoid exposing sensitive information

### Reporting Security Issues

Please report security vulnerabilities privately to the maintainers rather than creating public issues.

## Getting Help

### Resources

- **Documentation**: Read all documentation files
- **Issues**: Check existing GitHub issues
- **Discussions**: Use GitHub Discussions for questions

### Contact

- **GitHub Issues**: For bugs and feature requests
- **GitHub Discussions**: For questions and general discussion
- **Email**: For security issues and private matters

## Recognition

Contributors will be recognized in:

- **CHANGELOG.md**: For significant contributions
- **README.md**: For major features or fixes
- **GitHub**: Through commit history and pull request credits

## License

By contributing, you agree that your contributions will be licensed under the same MIT License that covers the project.

---

Thank you for contributing to Laravel Memory Profiler! Your efforts help make this package better for the entire Laravel community.

