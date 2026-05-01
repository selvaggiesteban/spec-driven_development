# TypeScript — Dependencies

**Technology**: TypeScript
**Dimension**: 5 of 7 - Dependencies

## Dependencies Checklist

### Core Dependencies
- [ ] typescript (core compiler)
- [ ] @types/node (Node.js type definitions)
- [ ] ts-node (execution in development)
- [ ] tslib (runtime helper functions)

### Development Dependencies
- [ ] @types/* packages for third-party libraries
- [ ] ts-jest or ts-mocha (testing)
- [ ] eslint + @typescript-eslint/* (linting)
- [ ] nodemon (auto-reload in development)

### Framework-specific Types
- [ ] @types/express (if using Express)
- [ ] @types/jest (if using Jest)
- [ ] @types/node (always required for Node.js)
- [ ] SDK-specific types when available

### Optional Enhancements
- [ ] ts-node-dev (combined nodemon + ts-node)
- [ ] type-coverage (measure type coverage)
- [ ] tsc-watch (watch mode alternative)
- [ ] @typescript-eslint/eslint-plugin

## Quality Control - Dependencies

### Version Control
- [ ] package.json y package-lock.json versionados
- [ ] Versiones específicas (no `^` o `~` en producción crítica)
- [ ] TypeScript version compatible con Node.js target
- [ ] @types/* versions compatibles con paquetes principales

### Security
- [ ] `npm audit` sin vulnerabilidades críticas
- [ ] Dependencias actualizadas regularmente
- [ ] No dependencias no utilizadas
- [ ] License compliance verificado

### Performance
- [ ] node_modules size razonable (< 500MB para proyectos medianos)
- [ ] Dependencias de desarrollo separadas (`devDependencies`)
- [ ] Production dependencies mínimas
- [ ] Tree-shaking configurado correctamente

### Testing
- [ ] Todas las dependencias testeadas
- [ ] Tests pasan con versiones actuales
- [ ] CI/CD usa `npm ci` para reproducibilidad
- [ ] Lockfile actualizado
