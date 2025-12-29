# Laravel Pint Auto-Formatting Setup

Automatically format your Laravel code on every commit using Laravel Pint and Husky pre-commit hooks.

---

## ✨ Features

- ✅ **Auto-format on commit** - Code gets formatted before every commit
- ✅ **4-space indentation** - Laravel standard
- ✅ **Same-line braces** - Classes and functions
- ✅ **Sorted imports** - Organized by length
- ✅ **Single quotes** - Consistent string formatting
- ✅ **No unused imports** - Automatic cleanup
- ✅ **Team-ready** - Works for everyone who clones the repo

---

## 📋 Requirements

- PHP 8.1+
- Composer
- Node.js & npm (for Husky)

---

## 🚀 Quick Start

### 1. Install Pint

```bash
composer require laravel/pint --dev
```

### 2. Add Configuration

Create `pint.json` in your Laravel root directory:

```json
{
    "preset": "laravel",
    "rules": {
        "curly_braces_position": {
            "functions_opening_brace": "same_line",
            "classes_opening_brace": "same_line"
        },
        "class_definition": {
            "single_line": true,
            "space_before_parenthesis": false
        },
        "array_syntax": {
            "syntax": "short"
        },
        "array_indentation": true,
        "binary_operator_spaces": {
            "default": "single_space"
        },
        "blank_line_before_statement": {
            "statements": ["return"]
        },
        "concat_space": {
            "spacing": "one"
        },
        "method_argument_space": {
            "on_multiline": "ensure_fully_multiline"
        },
        "method_chaining_indentation": true,
        "new_with_braces": true,
        "no_unused_imports": true,
        "ordered_imports": {
            "sort_algorithm": "length"
        },
        "single_quote": true,
        "trailing_comma_in_multiline": {
            "elements": ["arrays"]
        }
    },
    "exclude": [
        "node_modules",
        "vendor",
        "storage",
        "bootstrap/cache"
    ]
}
```

### 3. Install Husky

```bash
npm install --save-dev husky
```

### 4. Initialize Husky

```bash
npx husky init
```

This creates a `.husky/` directory in your project.

### 5. Create Pre-Commit Hook

```bash
echo '#!/bin/sh
./vendor/bin/pint --dirty' > .husky/pre-commit

chmod +x .husky/pre-commit
```

### 6. Commit Everything

```bash
git add pint.json .husky/ package.json package-lock.json
git commit -m "chore: add Pint auto-formatting with pre-commit hook"
git push
```

---

## 🎯 How It Works

```
┌─────────────────────────────────────────┐
│  You make code changes                  │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  git add .                              │
│  git commit -m "feat: add feature"      │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Pre-commit hook runs automatically     │
│  → ./vendor/bin/pint --dirty            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Code gets formatted                    │
│  → Only changed files (fast!)           │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  Formatted code is committed            │
└──────────────┬──────────────────────────┘
               │
               ▼
┌─────────────────────────────────────────┐
│  git push (already formatted!)          │
└─────────────────────────────────────────┘
```

**No sync issues!** Everything happens locally before push.

---

## 📚 Usage

### Manual Formatting

Format entire codebase:
```bash
./vendor/bin/pint
```

Format specific directory:
```bash
./vendor/bin/pint app/Http/Controllers/
```

Format specific file:
```bash
./vendor/bin/pint app/Services/FeatureGate.php
```

Format only changed files:
```bash
./vendor/bin/pint --dirty
```

### Preview Changes (Without Modifying)

See what would change:
```bash
./vendor/bin/pint --test
```

See detailed diff:
```bash
./vendor/bin/pint --test -v
```

### Bypass Pre-Commit Hook

Skip formatting for a specific commit:
```bash
git commit --no-verify -m "WIP: work in progress"
```

---

## 🎨 Code Style Examples

### Classes and Methods
```php
class UserService {
    public function create(array $data): User {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);
        
        return $user;
    }
}
```

### Arrays
```php
$config = [
    'enabled' => true,
    'timeout' => 30,
    'retries' => 3,
];
```

### Method Chaining
```php
$users = User::where('active', true)
    ->where('verified', true)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Imports (Sorted by Length)
```php
use App\Models\User;
use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
```

---

## 👥 Team Setup

When a team member clones your repo:

```bash
# 1. Clone repo
git clone https://github.com/your/repo.git
cd repo

# 2. Install dependencies
composer install
npm install  # ← This sets up Husky hooks automatically!

# 3. Done! Pre-commit hook is ready
git commit -m "test"  # Pint runs automatically
```

**No manual setup required!** ✅

---

## 🔧 Configuration

### Customize Rules

Edit `pint.json` to change formatting rules.

**Change import sorting:**
```json
"ordered_imports": {
    "sort_algorithm": "alpha"  // "length" or "alpha" or "none"
}
```

**Change brace position:**
```json
"curly_braces_position": {
    "classes_opening_brace": "next_line"  // "same_line" or "next_line"
}
```

### Exclude Files/Directories

Add to `pint.json`:
```json
"exclude": [
    "node_modules",
    "vendor",
    "storage",
    "bootstrap/cache",
    "app/Legacy/"  // Add your own exclusions
]
```

---

## ⚙️ Advanced

### Run Pint in CI/CD

Add to GitHub Actions workflow:

```yaml
- name: Run Pint
  run: ./vendor/bin/pint --test
```

This checks if code is formatted without modifying files.

### VSCode Integration

Install "Laravel Pint" extension:
1. Open VSCode Extensions
2. Search: "Laravel Pint"
3. Install: "open-southeners.laravel-pint"
4. Enable "Format on Save"

Now Pint runs automatically when you save files!

### PHPStorm Integration

1. Go to Settings → Tools → External Tools
2. Click "+"
3. Name: "Laravel Pint"
4. Program: `$ProjectFileDir$/vendor/bin/pint`
5. Arguments: `$FilePath$`
6. Working directory: `$ProjectFileDir$`

---

## 🐛 Troubleshooting

### Hook not running?

**Check permissions:**
```bash
chmod +x .husky/pre-commit
```

**Verify Husky is installed:**
```bash
npm list husky
```

**Reinstall Husky:**
```bash
rm -rf .husky
npx husky init
echo '#!/bin/sh
./vendor/bin/pint --dirty' > .husky/pre-commit
chmod +x .husky/pre-commit
```

### Pint command not found?

**Install Pint:**
```bash
composer require laravel/pint --dev
```

**Check Pint exists:**
```bash
ls -la vendor/bin/pint
```

### Commit takes too long?

The `--dirty` flag only formats changed files (fast).

If still slow, check what files are being formatted:
```bash
./vendor/bin/pint --dirty --test -v
```

### Want to skip hook temporarily?

```bash
git commit --no-verify -m "your message"
```

---

## 📁 Project Structure

```
your-laravel-project/
├── .husky/
│   ├── _/                    # Husky internals
│   └── pre-commit           # Pint hook ✅
├── pint.json                # Pint configuration ✅
├── package.json             # Has Husky dependency ✅
├── package-lock.json        # Lock file ✅
├── composer.json            # Has Pint dependency ✅
└── .gitignore               # Should NOT ignore .husky/
```

---

## 🔄 Workflow Comparison

### Without Auto-Format
```bash
# Write code
vim app/Services/UserService.php

# Manually format
./vendor/bin/pint

# Commit
git add .
git commit -m "feat: add feature"
git push
```

### With Auto-Format ✨
```bash
# Write code
vim app/Services/UserService.php

# Commit (formatting happens automatically!)
git add .
git commit -m "feat: add feature"
git push
```

**No extra steps!** 🎉

---

## 📖 Documentation

- [Laravel Pint Documentation](https://laravel.com/docs/pint)
- [PHP-CS-Fixer Rules](https://cs.symfony.com/doc/rules/)
- [Husky Documentation](https://typicode.github.io/husky/)

---

## 💡 Tips

1. **Run Pint before big commits:**
   ```bash
   ./vendor/bin/pint
   git add .
   git commit -m "style: format codebase"
   ```

2. **Check what will be formatted:**
   ```bash
   ./vendor/bin/pint --test -v
   ```

3. **Format only staged files:**
   ```bash
   ./vendor/bin/pint --dirty
   ```

4. **VSCode format-on-save** = Ultimate productivity!

---

## 🎯 Benefits

✅ **Consistent code style** across entire team
✅ **No formatting debates** - Pint decides
✅ **Cleaner git diffs** - No whitespace changes mixed with logic
✅ **Zero effort** - Happens automatically
✅ **Faster code reviews** - Focus on logic, not style
✅ **Professional codebase** - Follows Laravel standards

---

## 📝 License

This configuration is free to use in your Laravel projects.

---

## 🤝 Contributing

Found an issue or have a suggestion? Open an issue or submit a PR!

---

## ⭐ Credits

- [Laravel Pint](https://github.com/laravel/pint) - Official Laravel code formatter
- [PHP-CS-Fixer](https://github.com/PHP-CS-Fixer/PHP-CS-Fixer) - Underlying formatting engine
- [Husky](https://github.com/typicode/husky) - Git hooks made easy
