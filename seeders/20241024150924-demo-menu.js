'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up (queryInterface, Sequelize) {
    await queryInterface.bulkInsert('menus', [
    {name:"Catalogos",active:1},
    {name:"Status",active:1,submenu:1},
    {name:"Tipos",active:1,submenu:1},
    {name:"Usuarios",active:1},

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
    await queryInterface.bulkDelete('menus', null, {});
  {}
  }
};
