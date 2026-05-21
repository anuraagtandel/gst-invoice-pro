[OPEN] Debug Session: invoice-autocomplete-stops

## Symptom
- Invoice Create: product suggestions work on first search, then stop appearing after clearing the text and typing again.

## Repro Steps
1. Open http://127.0.0.1:8001/app/invoices/create
2. Click Product “Search product…” input
3. Type something → suggestions appear
4. Delete input text fully
5. Type again → suggestions no longer appear

## Hypotheses (Falsifiable)
- H1: Input event still fires, but dropdown visibility state (`showResults`) remains false after the first cycle.
- H2: Input event stops firing (listener detached / Alpine scope destroyed) after the first cycle.
- H3: Suggestions are computed (`filteredProducts.length > 0`) but dropdown is not rendered (x-show condition false or DOM clipped/z-index issue).
- H4: Search value updates, but product list reference becomes unavailable (scope resolution to `products` breaks after lifecycle change).
- H5: Dropdown renders but is immediately closed by `click.away` / blur sequencing after the first cycle.

## Evidence Plan
- Instrument product search components to report: focus/input/keyup/away/select, search value length, showResults, computed filtered length, row index, and whether this is mobile/salesman mode.
- Capture logs for two runs: pre (first cycle) and after-clear (second cycle).

## Status
- Instrumentation: pending
- Fix: not started (investigation only)
