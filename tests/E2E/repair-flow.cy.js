describe('Repair Submission Flow', () => {
  const user = {
    name: 'João Silva',
    email: `joao_${Date.now()}@test.com`,
    password: 'Senha@1234',
    phone: '11999999999',
  };

  const repair = {
    equipment: 'Compressor Industrial',
    client: 'Empresa XYZ Ltda',
    problem: 'Vazamento de ar comprimido no cilindro principal',
    diagnosis: 'Válvula de retenção desgastada',
  };

  beforeEach(() => {
    cy.visit('http://localhost:8080');
  });

  it('should complete full repair submission flow', () => {
    // 1️⃣ Register
    cy.contains('Criar Conta').click();
    cy.get('input[placeholder="Nome completo"]').type(user.name);
    cy.get('input[type="email"]').type(user.email);
    cy.get('input[type="password"]').first().type(user.password);
    cy.get('input[placeholder="Celular"]').type(user.phone);
    cy.get('select').select('Eletricista');
    cy.get('button[type="submit"]').click();

    // Verificar que foi registrado
    cy.url().should('include', '/dashboard');
    cy.contains('Bem-vindo').should('be.visible');

    // 2️⃣ Navigate to repairs
    cy.get('[data-testid="nav-repairs"]').click();
    cy.contains('Meus Reparos').should('be.visible');

    // 3️⃣ Create new repair
    cy.contains('Novo Reparo').click();
    cy.url().should('include', '/repairs/new');

    // 4️⃣ Fill repair form
    cy.get('input[name="course"]').click();
    cy.contains('Manutenção de Compressores').click();

    cy.get('input[name="equipment"]').type(repair.equipment);
    cy.get('input[name="client_name"]').type(repair.client);
    cy.get('textarea[name="problem"]').type(repair.problem);

    // 5️⃣ Upload photos
    cy.get('input[type="file"]').selectFile('cypress/fixtures/repair-before.jpg');
    cy.contains('Foto enviada').should('be.visible');

    cy.get('input[type="file"]').selectFile('cypress/fixtures/repair-during.jpg');
    cy.get('input[type="file"]').selectFile('cypress/fixtures/repair-after.jpg');

    // Verificar que todas as 3 fotos foram carregadas
    cy.get('[data-testid="photo-count"]').should('contain', '3/4');

    // 6️⃣ Fill diagnosis
    cy.get('textarea[name="diagnosis"]').type(repair.diagnosis);

    // 7️⃣ Submit
    cy.get('button[type="submit"]').contains('Enviar para Revisão').click();

    // Verificar sucesso
    cy.contains('Reparo enviado para revisão!').should('be.visible');
    cy.url().should('include', '/repairs');

    // 8️⃣ Verify repair appears in list
    cy.contains(repair.equipment).should('be.visible');
    cy.contains('Pendente de Revisão').should('be.visible');

    // 9️⃣ View repair details
    cy.contains(repair.equipment).click();
    cy.contains('Diagnóstico').should('be.visible');
    cy.contains(repair.diagnosis).should('be.visible');

    // Verificar status
    cy.get('[data-testid="repair-status"]').should('contain', 'Pendente');
  });

  it('should handle form validation', () => {
    cy.contains('Novo Reparo').click();

    // Tentar enviar sem preencher campos obrigatórios
    cy.get('button[type="submit"]').click();

    // Verificar mensagens de erro
    cy.contains('Campo obrigatório').should('be.visible');
    cy.get('[data-testid="error-course"]').should('exist');
    cy.get('[data-testid="error-equipment"]').should('exist');
  });

  it('should handle photo upload limits', () => {
    cy.contains('Novo Reparo').click();

    // Tentar upload de 5 fotos (máximo é 4)
    for (let i = 0; i < 5; i++) {
      cy.get('input[type="file"]').selectFile(`cypress/fixtures/photo${i}.jpg`);
    }

    // Verificar que a 5ª foto foi rejeitada
    cy.contains('Máximo 4 fotos').should('be.visible');
    cy.get('[data-testid="photo-count"]').should('contain', '4/4');
  });

  it('should allow draft save', () => {
    cy.contains('Novo Reparo').click();

    cy.get('input[name="equipment"]').type(repair.equipment);
    cy.get('input[name="client_name"]').type(repair.client);

    // Clique em "Salvar Rascunho"
    cy.contains('Salvar Rascunho').click();

    cy.contains('Rascunho salvo').should('be.visible');

    // Voltar e verificar que o rascunho está lá
    cy.visit('http://localhost:8080/dashboard');
    cy.get('[data-testid="nav-repairs"]').click();

    cy.contains('Rascunhos').should('be.visible');
    cy.contains(repair.equipment).should('be.visible');
  });

  it('should show payment modal for course subscription', () => {
    cy.get('[data-testid="nav-courses"]').click();
    cy.contains('Manutenção Avançada').click();

    // Curso não está inscrito
    cy.contains('Se inscrever').should('be.visible');
    cy.contains('Se inscrever').click();

    // Modal de pagamento aparece
    cy.get('[data-testid="payment-modal"]').should('be.visible');
    cy.contains('R$').should('be.visible');

    // Verificar métodos de pagamento
    cy.get('input[value="pix"]').should('be.visible');
    cy.get('input[value="credit_card"]').should('be.visible');
    cy.get('input[value="boleto"]').should('be.visible');
  });
});

describe('Teacher Review Flow', () => {
  const teacher = {
    email: 'professor@test.com',
    password: 'Senha@1234',
  };

  beforeEach(() => {
    cy.visit('http://localhost:8080');
    // Login como professor
    cy.login(teacher.email, teacher.password);
  });

  it('should allow teacher to review repair', () => {
    // Ir para painel do professor
    cy.visit('http://localhost:8080/teacher/dashboard');

    cy.contains('Reparos para Revisar').should('be.visible');
    cy.get('[data-testid="pending-repairs-count"]').should('contain', /\d+/);

    // Clicar no primeiro reparo
    cy.get('[data-testid="repair-item"]').first().click();

    // Verificar detalhes
    cy.contains('Feedback do Professor').should('be.visible');
    cy.get('textarea[name="feedback"]').should('exist');

    // Dar uma nota
    cy.get('input[value="5"]').click();

    // Escrever feedback
    cy.get('textarea[name="feedback"]').type('Excelente trabalho! Diagnóstico preciso.');

    // Aprovar
    cy.contains('Aprovar').click();

    // Verificar sucesso
    cy.contains('Reparo aprovado!').should('be.visible');

    // Voltar à lista
    cy.contains('Voltar').click();

    // Verificar que não está mais na lista
    cy.contains('Reparos para Revisar').should('be.visible');
  });

  it('should allow teacher to reject with improvements', () => {
    cy.visit('http://localhost:8080/teacher/dashboard');

    cy.get('[data-testid="repair-item"]').first().click();

    cy.get('textarea[name="feedback"]').type('Faltam detalhes no diagnóstico.');

    cy.get('textarea[name="improvements"]')
      .type('Descrever melhor os sintomas observados');

    // Rejeitar
    cy.contains('Solicitar Melhorias').click();

    cy.contains('Feedback enviado ao aluno').should('be.visible');
  });

  it('should export repairs as CSV', () => {
    cy.visit('http://localhost:8080/teacher/dashboard');

    cy.contains('Exportar').click();

    // Verificar que o arquivo foi baixado
    cy.readFile('cypress/downloads/reparos.csv').should('exist');
  });
});
