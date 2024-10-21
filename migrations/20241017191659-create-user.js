'use strict';
/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    await queryInterface.createTable('Users', {
      id: {
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
        type: Sequelize.INTEGER
      },
      Name: {
        type: Sequelize.STRING
      },
      PaternalName: {
        type: Sequelize.STRING
      },
      MaternalName: {
        type: Sequelize.STRING,
        allowNull: true
      },
      Email: {
        type: Sequelize.STRING,
        unique: true
      },
      Password: {
        type: Sequelize.STRING
      },
      Departamento: {
        type: Sequelize.INTEGER, // Cambia a INTEGER para coincidir con el tipo de id en departamentos
        references: {
          model: 'departamentos', // Nombre de la tabla relacionada
          key: 'id'               // Columna a la que hace referencia
        },
        onUpdate: 'CASCADE',      // Actualiza en cascada si se modifica el departamento
        onDelete: 'SET NULL',     // Establece NULL si se elimina el departamento
        allowNull: true            // Permitir nulos si no se requiere un departamento
      },
      createdAt: {
        allowNull: false,
        type: Sequelize.DATE
      },
      updatedAt: {
        allowNull: false,
        type: Sequelize.DATE
      }
    });
  },

  async down(queryInterface, Sequelize) {
    await queryInterface.dropTable('Users');
  }
};
