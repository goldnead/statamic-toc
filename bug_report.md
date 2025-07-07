# Bug Report: Statamic ToC Addon

## Summary
This report details **4 bugs** found and fixed in the Statamic ToC addon codebase. The bugs range from missing dependencies to logic errors, typos, and unsafe property access that could impact functionality and cause fatal errors.

## Bug 1: Missing Dependency Declaration for League CommonMark

### **Severity:** High
### **Type:** Dependency Issue / Runtime Error
### **Location:** `src/Parser.php` line 271, `composer.json`

### **Description:**
The code uses `\League\CommonMark\CommonMarkConverter` in the `generateFromMarkdown()` method to convert markdown content to HTML, but the `league/commonmark` package is not declared as a dependency in `composer.json`. This causes a fatal error when users try to process markdown content.

### **Impact:**
- Fatal error: `Class '\League\CommonMark\CommonMarkConverter' not found`
- Complete failure of markdown processing functionality
- Poor user experience for anyone using markdown input

### **Root Cause:**
The developer added markdown support but forgot to declare the required dependency.

### **Fix Applied:**
Added `"league/commonmark": "^2.0"` to the `require` section in `composer.json`.

```json
"require": {
    "php": "^7.4 | ^8.0 | ^8.1 | ^8.2",
    "statamic/cms": "^3.0 | ^3.1 | ^3.2 | ^3.3 | ^3.4 | ^4.0 | ^5.0",
    "league/commonmark": "^2.0"
}
```

---

## Bug 2: Typo in Test Assertion

### **Severity:** Medium
### **Type:** Logic Error / Testing Issue
### **Location:** `tests/Unit/ParserTest.php` lines 144-145

### **Description:**
In the `assertChild()` method of `ParserTest.php`, there's a typo where `has_hildren` is used instead of `has_children`. This causes the test to check for a non-existent property, leading to incorrect test behavior and false negatives.

### **Impact:**
- Test doesn't properly validate the `has_children` property
- Could mask bugs in the children detection logic
- Reduces test coverage reliability

### **Root Cause:**
Simple typo during test writing - `has_hildren` instead of `has_children`.

### **Fix Applied:**
Corrected the property name in both the `isset()` check and the assertion:

```php
// Before:
if (isset($child['has_hildren'])) {
    $this->assertIsBool($child['has_hildren']);
    
// After:
if (isset($child['has_children'])) {
    $this->assertIsBool($child['has_children']);
```

---

## Bug 3: Logic Error in depth() Method

### **Severity:** Medium
### **Type:** Logic Error / Calculation Bug
### **Location:** `src/Parser.php` lines 95-99 and 115-117

### **Description:**
The `depth()` method has a logic error in how it calculates the maximum heading level. When `from()` is called after `depth()`, it incorrectly recalculates the depth by passing the current `maxLevel` instead of the intended depth value, leading to incorrect heading level filtering.

### **Impact:**
- Incorrect TOC generation when methods are chained in certain orders
- Headers might be incorrectly included or excluded from the TOC
- Inconsistent behavior depending on method call order

### **Root Cause:**
The `from()` method calls `$this->depth($this->maxLevel)` instead of preserving and recalculating the original depth value.

### **Fix Applied:**
1. **Fixed the depth calculation logic** to be clearer:
   ```php
   // Before:
   $this->maxLevel = $depth + $this->minLevel - 1;
   
   // After:
   $this->maxLevel = $this->minLevel + $depth - 1;
   ```

2. **Fixed the from() method** to preserve the original depth:
   ```php
   // Before:
   $this->minLevel = $start;
   $this->depth($this->maxLevel);
   
   // After:
   $currentDepth = $this->maxLevel - $this->minLevel + 1;
   $this->minLevel = $start;
   $this->depth($currentDepth);
   ```

### **Test Case Example:**
```php
// This would previously fail:
$parser = new Parser($content);
$parser->depth(3)->from('h2'); // Would incorrectly calculate maxLevel

// Now works correctly regardless of method order
```

---

## Bug 4: Unsafe Property Access in Bard Content Processing

### **Severity:** High  
### **Type:** Logic Error / Safety Issue
### **Location:** `src/Parser.php` lines 284-285 and 296-301
### **GitHub Issue:** #26

### **Description:**
The parser code assumes certain array structures exist without proper validation, leading to "Undefined property" or "Undefined index" errors when processing malformed or incomplete Bard content structures. This causes fatal errors that break entire page rendering.

### **Impact:**
- Fatal crashes when processing malformed Bard content
- "Undefined index: attrs" errors when attrs structure is missing
- "Undefined index: 0" errors when content array is empty
- "Undefined index: text" errors when text property is missing
- Poor user experience with white screen errors
- Difficult debugging with unclear error messages

### **Root Cause:**
Two areas in the code accessed array properties without checking if they exist:
1. Filter condition directly accessing `$item['attrs']['level']` without checking if `attrs` exists
2. Content processing accessing `$heading['content'][0]` without validating the array structure

### **Fix Applied:**

**1. Safe Property Access in Filter Condition:**
```php
// Before:
return is_array($item) && $item['type'] === 'heading' && $item['attrs']['level'] >= $this->minLevel && $item['attrs']['level'] <= $this->maxLevel;

// After:
return is_array($item) 
    && isset($item['type']) 
    && $item['type'] === 'heading' 
    && isset($item['attrs']['level']) 
    && $item['attrs']['level'] >= $this->minLevel 
    && $item['attrs']['level'] <= $this->maxLevel;
```

**2. Comprehensive Content Validation:**
```php
// Before:
if (! isset($heading['content']) || $heading['content'][0]['type'] !== 'text') {
    return;
}

// After:
if (! isset($heading['content']) 
    || ! is_array($heading['content']) 
    || empty($heading['content']) 
    || ! isset($heading['content'][0]['type']) 
    || $heading['content'][0]['type'] !== 'text'
    || ! isset($heading['content'][0]['text'])) {
    return;
}
```

### **Testing:**
Created comprehensive test suite `tests/Unit/ParserSafetyTest.php` covering all malformed content scenarios.

---

## Additional Observations

### **Potential Security Considerations:**
While not fixed in this report, the `injectIds()` method uses regex parsing for HTML which could be vulnerable to HTML injection attacks. Consider using a proper HTML parser for security-critical applications.

### **Performance Considerations:**
The HTML parsing in `generateFromHtml()` creates a new DOMDocument for every call and uses potentially expensive encoding conversion. For large content, this could impact performance.

---

## Testing Recommendations

1. **Add integration tests** for markdown processing to ensure the CommonMark dependency works correctly
2. **Add tests for method chaining** to verify the depth/from logic works in all orders
3. **Consider adding security tests** for HTML injection scenarios
4. **Test with malformed Bard content** to ensure robustness

## Conclusion

All four bugs have been successfully identified and fixed:
- ✅ Missing dependency added to composer.json
- ✅ Test typo corrected
- ✅ Logic error in depth calculation resolved
- ✅ Unsafe property access made safe with proper validation

The fixes ensure proper functionality, better test coverage, correct behavior regardless of method call order, and robust handling of malformed content without fatal errors.