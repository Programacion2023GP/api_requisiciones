'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up (queryInterface, Sequelize) {
    await queryInterface.bulkInsert('users', [
      { name: "Luis Angel", paternalname: 'Gutierrez', maternalname: "Hernandez", email: "luis1@gmail.com", id_group: 1 },
      { name: "Carlos Alberto", paternalname: 'Mendez', maternalname: "Torres", email: "carlos@gmail.com", id_group: 2 },
      { name: "María José", paternalname: 'López', maternalname: "Sánchez", email: "maria@gmail.com", id_group: 1 },
      { name: "Ana María", paternalname: 'Cruz', maternalname: "Gonzalez", email: "ana@gmail.com", id_group: 3 },
      { name: "Javier", paternalname: 'Fernandez', maternalname: "Lopez", email: "javier@gmail.com", id_group: 2 },
      { name: "Lucía", paternalname: 'Reyes', maternalname: "Jiménez", email: "lucia@gmail.com", id_group: 1 },
      { name: "Miguel", paternalname: 'Salazar', maternalname: "Rojas", email: "miguel@gmail.com", id_group: 3 },
      { name: "Sofía", paternalname: 'Morales', maternalname: "Díaz", email: "sofia@gmail.com", id_group: 1 },
      { name: "Roberto", paternalname: 'Hernandez', maternalname: "Pérez", email: "roberto@gmail.com", id_group: 2 },
      { name: "Isabel", paternalname: 'Castillo', maternalname: "Cordero", email: "isabel@gmail.com", id_group: 3 }
    ]);
    
 
    /**
     * Add seed commands here.
     *
     * Example:
     * await queryInterface.bulkInsert('People', [{
     *   name: 'John Doe',
     *   isBetaMember: false
     * }], {});
    */
  },

  async down (queryInterface, Sequelize) {
    await queryInterface.bulkDelete('users', null, {});

    /**
     * Add commands to revert seed here.
     *
     * Example:
     * await queryInterface.bulkDelete('People', null, {});
     */
  }
};
