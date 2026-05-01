##### 5.12.1.1 Scalability
- Tables have indexes on frequently searched columns
- Appropriate normalization is used (avoid duplication, but don't over-normalize)
- Complex queries use EXPLAIN for optimization
- Pagination is implemented in queries that return many records
- Large tables have partitioning strategy if necessary
