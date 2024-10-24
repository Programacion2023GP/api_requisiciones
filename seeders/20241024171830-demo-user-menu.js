'use strict';

/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up (queryInterface, Sequelize) {

    await queryInterface.bulkInsert('usermenus', [
      {id_user:1,id_menu:2,read:true,write:true,delete:false,edit:true},
      {id_user:2,id_menu:3,read:true,write:true,delete:false,edit:true},
      {id_user:1,id_menu:3,read:true,write:true,delete:true,edit:true},
      {id_user:3,id_menu:2,read:true,write:false,delete:true,edit:true},
      {id_user:4,id_menu:3,read:true,write:true,delete:false,edit:true},
      {id_user:5,id_menu:2,read:true,write:true,delete:true,edit:true},

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
    await queryInterface.bulkDelete('usersmenu', null, {});

    /**
     * Add commands to revert seed here.
     *
     * Example:
     * await queryInterface.bulkDelete('People', null, {});
     */
  }
};
