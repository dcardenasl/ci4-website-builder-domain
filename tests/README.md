## Fixture policy

General Unit, Integration and Feature tests create deterministic synthetic data
through `tests/Support/Fixtures`. They must not execute database seeders. The
canonical starter content is tested only by the `SeederContracts` suite under
`tests/Integration/Database/Seeds`.

Run the suites independently when changing fixtures or starter content:

```bash
composer test:dynamic
composer test:seed-contracts
composer test:fixture-policy
```

The policy check rejects seeder execution from general tests while allowing
exact content assertions inside the explicit seeder-contract directory.
