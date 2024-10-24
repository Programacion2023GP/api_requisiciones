"use strict";
/** @type {import('sequelize-cli').Migration} */
module.exports = {
  async up(queryInterface, Sequelize) {
    await queryInterface.createTable("usermenus", {
      id: {
        allowNull: false,
        autoIncrement: true,
        primaryKey: true,
        type: Sequelize.INTEGER,
      },
      id_user: {
        type: Sequelize.INTEGER,
        references: {
          model: "users", // Nombre de la tabla relacionada
          key: "id", // Columna a la que hace referencia
        },

        onUpdate: "CASCADE", // Actualiza en cascada si se modifica el departamento
        onDelete: "SET NULL", // Establece NULL si se elimina el departamento
        allowNull: true, // Permitir nulos si no se requiere un departamento
      },
      id_menu: {
        type: Sequelize.INTEGER,
        references: {
          model: "menus", // Nombre de la tabla relacionada
          key: "id", // Columna a la que hace referencia
        },

        onUpdate: "CASCADE", // Actualiza en cascada si se modifica el departamento
        onDelete: "SET NULL", // Establece NULL si se elimina el departamento
        allowNull: true,
      },
      read: {
        type: Sequelize.BOOLEAN,
        defaultValue: false,
      },
      write: { type: Sequelize.BOOLEAN, defaultValue: false },
      edit: { type: Sequelize.BOOLEAN, defaultValue: false },
      delete: { type: Sequelize.BOOLEAN, defaultValue: false },
      createdAt: {
        allowNull: false,
        type: Sequelize.DATE,
        defaultValue: Sequelize.literal('CURRENT_TIMESTAMP'),
      },
      updatedAt: {
        allowNull: false,
        type: Sequelize.DATE,
        defaultValue: Sequelize.literal('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'),

      },
    });
  },
  async down(queryInterface, Sequelize) {
    await queryInterface.dropTable("usermenus");
  },
};
